<?php

namespace App\Http\Controllers;

use App\Models\CareRecipient;
use App\Services\Care\AccessControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CareDocumentController extends Controller
{
    public function __construct(private AccessControl $access) {}

    public function index(Request $r, CareRecipient $recipient)
    {
        $this->access->authorize($r->user(), $recipient, 'documents');

        return $recipient->documents()->latest()->get();
    }

    public function store(Request $r, CareRecipient $recipient)
    {
        $this->access->authorize($r->user(), $recipient, 'documents', true);
        $r->validate(['file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,txt,docx,xlsx|max:20480']);
        $f = $r->file('file');
        $path = $f->store('care/'.$recipient->organization_id.'/'.$recipient->id, 'local');
        try {
            $doc = $recipient->documents()->create(['created_by' => $r->user()->id, 'filename' => basename($f->getClientOriginalName()), 'path' => $path, 'mime_type' => $f->getMimeType(), 'size' => $f->getSize()]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return response()->json($doc, 201);
    }

    public function download(Request $r, CareRecipient $recipient, int $document)
    {
        $this->access->authorize($r->user(), $recipient, 'documents');
        $d = $recipient->documents()->findOrFail($document);
        abort_unless(Storage::disk('local')->exists($d->path), 404);

        return Storage::disk('local')->download($d->path, $d->filename, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function destroy(Request $r, CareRecipient $recipient, int $document)
    {
        $this->access->authorize($r->user(), $recipient, 'documents', true);
        $d = $recipient->documents()->findOrFail($document);
        abort_unless((int) $d->created_by === (int) $r->user()->id, 403, 'Somente o autor pode excluir este documento.');
        Storage::disk('local')->delete($d->path);
        $d->delete();

        return response()->noContent();
    }
}
