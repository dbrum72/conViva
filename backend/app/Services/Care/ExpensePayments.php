<?php

namespace App\Services\Care;

use App\Models\CareEntry;
use App\Models\CareRecipient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ExpensePayments
{
    public function __construct(private AccessControl $access, private ProposalGuard $guard) {}

    public function pay(User $user, CareRecipient $recipient, int $id, int $shareId, ?UploadedFile $file = null)
    {
        $path = null;
        try {
            return DB::transaction(function () use ($user, $recipient, $id, $shareId, $file, &$path) {
                $recipient = $this->guard->lock($recipient);
                $user->unsetRelation('roles')->unsetRelation('permissions');
                $this->access->authorize($user, $recipient, 'finance', true);
                $entry = $recipient->entries()->where('kind', 'expense')->lockForUpdate()->findOrFail($id);
                abort_unless(in_array($entry->status, ['pending', 'completed']), 422, 'Despesa ainda não aceita.');
                abort_if($entry->proposals()->where('status', 'pending')->exists(), 409, 'Existe uma proposta aguardando decisão.');
                $share = $entry->shares()->lockForUpdate()->findOrFail($shareId);
                abort_unless((int) $share->user_id === (int) $user->id, 403, 'Registre somente o pagamento da sua própria parcela.');
                if ($file) {
                    $this->access->authorize($user, $recipient, 'documents', true);
                    abort_if($share->receipt()->exists(), 409, 'Esta parcela já possui um comprovante. O arquivo original será preservado.');
                    $path = $file->store('care/'.$recipient->organization_id.'/'.$recipient->id.'/payments', 'local');
                    if (! $path) {
                        throw new RuntimeException('Não foi possível armazenar o comprovante. O pagamento não foi registrado.');
                    }
                    $share->receipt()->create([
                        'created_by' => $user->id, 'filename' => basename($file->getClientOriginalName()),
                        'path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                    ]);
                }
                $share->update(['paid_at' => $share->paid_at ?? now()]);
                if (! $entry->shares()->whereNull('paid_at')->exists()) {
                    $entry->update(['status' => 'completed', 'completed_at' => $entry->completed_at ?? now()]);
                }

                return $share;
            });
        } catch (Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
    }

    public function decorate(User $user, CareRecipient $recipient, CareEntry $entry): CareEntry
    {
        if ($entry->kind !== 'expense') {
            return $entry;
        }
        $canRead = $this->access->allowed($user, $recipient, 'documents') && $this->access->allowed($user, $recipient, 'finance');
        $canAttach = $this->access->allowed($user, $recipient, 'documents', true) && $this->access->allowed($user, $recipient, 'finance', true)
            && in_array($entry->status, ['pending', 'completed']) && ! $entry->proposals->contains('status', 'pending');
        $canPay = $this->access->allowed($user, $recipient, 'finance', true)
            && in_array($entry->status, ['pending', 'completed']) && ! $entry->proposals->contains('status', 'pending');
        foreach ($entry->shares as $share) {
            $share->setAttribute('can_pay', $canPay && ! $share->paid_at && (int) $share->user_id === (int) $user->id);
            $share->setAttribute('receipt', $canRead ? $share->receipt()->first(['id', 'expense_share_id', 'filename', 'mime_type', 'size', 'created_at']) : null);
            $share->setAttribute('can_attach_receipt', $canAttach && (int) $share->user_id === (int) $user->id && ! $share->receipt()->exists());
        }

        return $entry;
    }

    public function download(User $user, CareRecipient $recipient, int $entryId, int $shareId)
    {
        $this->access->authorize($user, $recipient, 'finance');
        $this->access->authorize($user, $recipient, 'documents');
        $entry = $recipient->entries()->where('kind', 'expense')->findOrFail($entryId);
        $share = $entry->shares()->findOrFail($shareId);
        $receipt = $share->receipt()->firstOrFail();
        abort_unless(Storage::disk('local')->exists($receipt->path), 404, 'Comprovante indisponível.');

        return Storage::disk('local')->download($receipt->path, $receipt->filename, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
