<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    use HasUuids;

    protected $table = 'sidulur_services';

    protected $casts = [
        'selesai_di_pendaftaran' => 'boolean',
        'butuh_verifikasi' => 'boolean',
        'is_active' => 'boolean',
        'sla_value' => 'integer',
    ];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function form(): HasOne
    {
        return $this->hasOne(Form::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    /** Identitas warga wajib untuk semua layanan, di luar schema formulir. */
    public const IDENTITY_REQUIREMENTS = [
        ['nama' => 'KTP', 'keterangan' => '', 'wajib' => true],
        ['nama' => 'Kartu Keluarga (KK)', 'keterangan' => '', 'wajib' => true],
        ['nama' => 'Nomor HP', 'keterangan' => '', 'wajib' => true],
    ];

    /**
     * Persyaratan tambahan dari schema formulir layanan.
     *
     * @return array<int, array{nama: string, keterangan: string, wajib: bool}>
     */
    public function requirements(): array
    {
        $extra = collect($this->form?->schema['persyaratan'] ?? [])
            // Baris warisan (sumber ktp/kk/hp) sudah otomatis untuk semua layanan.
            ->reject(fn ($row) => in_array($row['sumber'] ?? null, ['ktp', 'kk', 'hp'], true))
            ->map(fn ($row) => [
                'nama' => trim((string) ($row['nama'] ?? $row['label'] ?? '')),
                'keterangan' => trim((string) ($row['keterangan'] ?? '')),
                'wajib' => (bool) ($row['wajib'] ?? $row['required'] ?? false),
            ])
            ->filter(fn ($row) => $row['nama'] !== '');

        return collect(self::IDENTITY_REQUIREMENTS)->concat($extra)->values()->all();
    }
}
