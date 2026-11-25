<?php

namespace Bale\BaleSidulur\Services;

use Illuminate\Support\Facades\Config;

class SidulurConnection
{
    public static function register(): void
    {
        $config = config('sidulur.connection');
        $default = config('database.connections.'.config('database.default'), []);

        // SIDULUR_DB_* kosong: pakai kredensial koneksi default. Tanpa ini
        // PDO login anonim dan MySQL 8.4 menjawab "Plugin
        // 'mysql_native_password' is not loaded", bukan error yang jelas.
        $pick = fn (string $key, mixed $fallback) => filled($config[$key] ?? null) ? $config[$key] : $fallback;

        Config::set('database.connections.'.$config['name'], [
            'driver' => 'mysql',
            'host' => $pick('host', $default['host'] ?? '127.0.0.1'),
            'port' => $pick('port', $default['port'] ?? '3306'),
            'database' => $pick('database', $default['database'] ?? null),
            'username' => $pick('username', $default['username'] ?? null),
            'password' => $pick('password', $default['password'] ?? null),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => [
                \PDO::ATTR_TIMEOUT => 5,
            ],
        ]);
    }

    public static function name(): string
    {
        return config('sidulur.connection.name', 'sidulur');
    }

    /**
     * True bila konfigurasi koneksi tampak lengkap.
     */
    public static function configured(): bool
    {
        $config = config('sidulur.connection');

        return filled($config['database'] ?? null) && filled($config['username'] ?? null);
    }
}
