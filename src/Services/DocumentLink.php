<?php

namespace Bale\BaleSidulur\Services;

use Bale\BaleSidulur\Models\Document;
use Illuminate\Support\Facades\URL;

class DocumentLink
{
    public function for(Document $document): ?string
    {
        if (! $document->isPublic()) {
            return null;
        }

        $days = max(1, (int) config('sidulur.document_link_days', 30));

        return URL::temporarySignedRoute(
            'sidulur.document',
            now()->addDays($days),
            ['document' => $document->getKey()],
        );
    }
}
