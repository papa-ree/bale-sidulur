<?php

namespace Bale\BaleSidulur\Http\Controllers;

use Bale\BaleSidulur\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController
{
    public function __invoke(Document $document)
    {
        abort_unless($document->isPublic(), 404);

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->path), 404);

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
        ]);
    }
}
