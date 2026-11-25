<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelayananBidang extends SidulurModel
{
    protected $table = 'sidulur_pelayanan_bidangs';

    protected $casts = [
        'scheduled_at' => 'date',
        'verlap_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
