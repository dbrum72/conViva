<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Modules\Auth\Services\PasswordRecovery;
use Illuminate\Http\JsonResponse;

class PasswordRecoveryController extends Controller
{
    public function store(ForgotPasswordRequest $request, PasswordRecovery $recovery): JsonResponse
    {
        $recovery->requestLink($request->validated());

        return response()->json(['message' => 'Se houver uma conta com esse e-mail, você receberá as instruções para redefinir sua senha.']);
    }

    public function update(ResetPasswordRequest $request, PasswordRecovery $recovery): JsonResponse
    {
        $recovery->reset($request->validated());

        return response()->json(['message' => 'Senha atualizada. Entre com sua nova senha.']);
    }
}
