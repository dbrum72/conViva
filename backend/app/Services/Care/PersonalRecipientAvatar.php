<?php

namespace App\Services\Care;

use App\Models\CareRecipient;
use App\Models\CareRecipientAvatar;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PersonalRecipientAvatar
{
    public function recipient(User $user): CareRecipient
    {
        $recipient = app(CurrentOrganization::class)->get()->recipients()->where('status', 'active')->firstOrFail();
        abort_unless(app(AccessControl::class)->responsible($user, $recipient), 403);

        return $recipient;
    }

    public function find(User $user): ?CareRecipientAvatar
    {
        return CareRecipientAvatar::where('care_recipient_id', $this->recipient($user)->id)->where('user_id', $user->id)->first();
    }

    public function replace(User $user, ?UploadedFile $image): void
    {
        $recipient = $this->recipient($user);
        $path = $image?->store('recipient-avatars/'.$recipient->id.'/'.$user->id, 'local');
        abort_if($image && ! $path, 500, 'Não foi possível salvar a foto. Tente novamente.');
        try {
            $previousPath = DB::transaction(function () use ($user, $recipient, $path) {
                // Serialize replacements even when this user has no avatar yet.
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $query = CareRecipientAvatar::where('care_recipient_id', $recipient->id)->where('user_id', $user->id);
                $previous = $query->first();
                if ($path) {
                    CareRecipientAvatar::updateOrCreate(
                        ['care_recipient_id' => $recipient->id, 'user_id' => $user->id],
                        ['path' => $path],
                    );
                } else {
                    $previous?->delete();
                }

                return $previous?->path;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
        if ($previousPath) {
            Storage::disk('local')->delete($previousPath);
        }
    }
}
