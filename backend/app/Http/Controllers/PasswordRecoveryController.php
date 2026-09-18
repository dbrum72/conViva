<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\PasswordRecovery;
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
