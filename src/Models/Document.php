<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasUuids;

    public const PUBLIC_KINDS = ['hasil', 'surat'];

    protected $table = 'sidulur_documents';

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPublic(): bool
    {
        return in_array($this->kind, self::PUBLIC_KINDS, true);
    }
}
