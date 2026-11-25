<?php

namespace Bale\BaleSidulur\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends SidulurModel
{
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
