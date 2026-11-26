<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pendaftaran extends Model
{
    use HasUuids;

    protected $table = 'sidulur_pendaftarans';

    protected $casts = [
        'form_snapshot' => 'array',
        'forwarded_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function pelayananBidang(): HasOne
    {
        return $this->hasOne(PelayananBidang::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class)->orderBy('created_at');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Status publik yang tidak membocorkan detail internal.
     */
    public function publicStatus(): string
    {
        if ($this->status !== 'selesai') {
            return 'Diterima';
        }

        if (! $this->pelayananBidang) {
            return 'Selesai di pendaftaran';
        }

        return match ($this->pelayananBidang->status) {
            'selesai' => 'Selesai',
            'dijadwalkan' => 'Verifikasi lapangan dijadwalkan',
            'verlap_selesai' => 'Menunggu respon bidang',
            default => 'Diproses bidang',
        };
    }
}
