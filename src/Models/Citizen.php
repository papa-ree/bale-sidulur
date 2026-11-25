<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Citizen extends SidulurModel
{
    protected $table = 'sidulur_citizens';

    protected $hidden = ['nik', 'phone', 'nrs'];

    protected $casts = ['is_active' => 'boolean'];

    public function familyCard(): BelongsTo
    {
        return $this->belongsTo(FamilyCard::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
