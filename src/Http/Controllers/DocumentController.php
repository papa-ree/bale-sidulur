<?php

namespace Bale\BaleSidulur\Http\Controllers;

use Bale\BaleSidulur\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class DocumentController
{
    public function __invoke(Document $document)
    {
        abort_unless($document->isPublic(), 404);

        // Satu GET saja: exists() memicu HEAD yang bisa 403 di MinIO walau GET-nya sehat.
        $stream = Storage::disk($document->disk)->readStream($document->path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(fn () => fpassthru($stream), 200, [
            'Content-Type' => $document->mime ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                'attachment',
                (string) $document->original_name,
                Str::ascii((string) $document->original_name),
            ),
        ]);
    }
}
