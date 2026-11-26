<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bidang extends Model
{
    use HasUuids;

    protected $table = 'sidulur_bidangs';

    protected $casts = ['is_active' => 'boolean'];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
