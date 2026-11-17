<?php

namespace App\Modules\Care\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Care\Http\Requests\RecipientAvatarRequest;
use App\Modules\Care\Services\PersonalRecipientAvatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecipientAvatarController extends Controller
{
    public function show(Request $request, PersonalRecipientAvatar $avatars)
    {
        $avatar = $avatars->find($request->user());
        if (! $avatar || ! Storage::disk('local')->exists($avatar->path)) {
            return response()->noContent()->header('Cache-Control', 'private, no-store');
        }

        return Storage::disk('local')->response($avatar->path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(RecipientAvatarRequest $request, PersonalRecipientAvatar $avatars)
    {
        $avatars->replace($request->user(), $request->file('image'));

        return response()->noContent();
    }

    public function destroy(Request $request, PersonalRecipientAvatar $avatars)
    {
        $avatars->replace($request->user(), null);

        return response()->noContent();
    }
}
