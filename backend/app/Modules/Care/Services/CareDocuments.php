<?php

namespace App\Modules\Care\Services;

use App\Models\CareDocument;
use App\Models\CareRecipient;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CareDocuments
{
    public function __construct(private AccessControl $access, private ProposalGuard $guard) {}

    public function listing(User $user, CareRecipient $recipient, array $filters): Collection
    {
        $this->access->authorize($user, $recipient, 'documents');
        $zone = $recipient->organization->timezone;
        $capabilities = $this->access->capabilities($user, $recipient);

        return $recipient->documents()->with('entry:id,care_recipient_id,kind,title')
            ->when(isset($filters['category']), fn ($query) => $query->where('category', $filters['category']))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', CarbonImmutable::parse($from, $zone)->utc()))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<', CarbonImmutable::parse($to, $zone)->addDay()->utc()))
            ->latest()->orderByDesc('id')->get()
            ->filter(fn ($document) => $this->contextVisible($document, $recipient, $capabilities))
            ->values();
    }

    public function upload(User $user, CareRecipient $recipient, array $data): CareDocument
    {
        $path = null;
        try {
            return DB::transaction(function () use ($user, $recipient, $data, &$path) {
                $recipient = $this->guard->lock($recipient);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $this->access->authorize($user, $recipient, 'documents', true);
                $this->authorizeLink($user, $recipient, $data['care_entry_id'] ?? null);
                $file = $data['file'];
                $path = $file->store('care/'.$recipient->organization_id.'/'.$recipient->id, 'local');
                abort_unless($path, 503, 'Não foi possível armazenar o documento. Tente novamente.');

                return $recipient->documents()->create([
                    'created_by' => $user->id,
                    'filename' => basename($file->getClientOriginalName()),
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'category' => $data['category'] ?? null,
                    'valid_until' => $data['valid_until'] ?? null,
                    'care_entry_id' => $data['care_entry_id'] ?? null,
                ]);
            });
        } catch (Throwable $failure) {
            if ($path) {
                try {
                    $this->removeFile($path);
                } catch (Throwable) {
                    Log::error('Falha ao limpar arquivo após erro de cadastro de documento.', ['organization_id' => $recipient->organization_id]);
                }
            }
            throw $failure;
        }
    }

    public function update(User $user, CareRecipient $recipient, int $id, array $data): CareDocument
    {
        return DB::transaction(function () use ($user, $recipient, $id, $data) {
            [$recipient, $document] = $this->lockOwnedDocument($user, $recipient, $id);
            if (array_key_exists('care_entry_id', $data)) {
                abort_if($document->care_entry_id !== null && (int) $document->care_entry_id !== (int) $data['care_entry_id'],
                    422, 'O vínculo do documento não pode ser removido ou substituído.');
                $this->authorizeLink($user, $recipient, $data['care_entry_id']);
            }
            $document->update($data);

            return $document->unsetRelation('entry');
        });
    }

    public function download(User $user, CareRecipient $recipient, int $id): StreamedResponse
    {
        $this->access->authorize($user, $recipient, 'documents');
        $document = $recipient->documents()->with('entry')->findOrFail($id);
        abort_unless($this->contextVisible($document, $recipient, $this->access->capabilities($user, $recipient)), 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->filename, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function delete(User $user, CareRecipient $recipient, int $id): void
    {
        DB::transaction(function () use ($user, $recipient, $id) {
            [, $document] = $this->lockOwnedDocument($user, $recipient, $id);
            $this->removeFile($document->path);
            abort_unless($document->delete(), 503, 'Não foi possível concluir a exclusão. Tente novamente.');
        });
    }

    private function lockOwnedDocument(User $user, CareRecipient $recipient, int $id): array
    {
        $recipient = $this->guard->lock($recipient);
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $this->access->authorize($user, $recipient, 'documents', true);
        $document = $recipient->documents()->with('entry')->lockForUpdate()->findOrFail($id);
        abort_unless($this->contextVisible($document, $recipient, $this->access->capabilities($user, $recipient)), 404);
        abort_unless((int) $document->created_by === (int) $user->id, 403, 'Somente o autor pode alterar ou excluir este documento.');

        return [$recipient, $document];
    }

    private function authorizeLink(User $user, CareRecipient $recipient, ?int $id): void
    {
        if ($id !== null) {
            $entry = $recipient->entries()->findOrFail($id);
            $this->access->authorize($user, $recipient, $this->access->area($entry->kind));
        }
    }

    private function contextVisible(CareDocument $document, CareRecipient $recipient, array $capabilities): bool
    {
        if ($document->care_entry_id === null) {
            return true;
        }

        return $document->entry
            && (int) $document->entry->care_recipient_id === (int) $recipient->id
            && $capabilities[$this->access->area($document->entry->kind)]['view'];
    }

    private function removeFile(string $path): void
    {
        $disk = Storage::disk('local');
        try {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
            $remains = $disk->exists($path);
        } catch (Throwable) {
            $remains = true;
        }
        abort_if($remains, 503, 'Não foi possível excluir o arquivo. Tente novamente.');
    }
}
