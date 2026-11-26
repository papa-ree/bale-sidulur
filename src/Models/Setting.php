<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Setting extends Model
{
    protected $table = 'sidulur_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public function getIncrementing(): bool
    {
        return false;
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        return static::query()->find($key)?->value ?? $default;
    }

    public static function checkPassword(?string $password): bool
    {
        $hash = static::value('dashboard_password');

        return is_string($hash) && $hash !== '' && Hash::check((string) $password, $hash);
    }
}
