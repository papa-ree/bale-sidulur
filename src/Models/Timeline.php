<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timeline extends SidulurModel
{
    protected $table = 'sidulur_timelines';

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
