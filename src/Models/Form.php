<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Form extends SidulurModel
{
    protected $table = 'sidulur_forms';

    protected $casts = [
        'schema' => 'array',
        'version' => 'integer',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
