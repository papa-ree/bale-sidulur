<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FamilyCard extends SidulurModel
{
    protected $table = 'sidulur_family_cards';

    protected $hidden = ['nomor_kk'];

    public function citizens(): HasMany
    {
        return $this->hasMany(Citizen::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
