<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Services\RegisterOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(
                'auth:api',
                except: [
                    'login',
                    'register',
                ],
            ),
        ];
    }

    public function register(
        RegisterRequest $request,
        RegisterOrganization $registrar,
    ): JsonResponse {
        $result =
            $registrar->execute(
                $request->validated()
            );

        $user =
            $result['user'];

        $organization =
            $result['organization'];

        $token =
            $this
                ->guard()
                ->login(
                    $user
                );

        return response()->json([
            'token' => $token,

            'access_token' => $token,

            'token_type' => 'bearer',

            'expires_in' => $this
                ->guard()
                ->factory()
                ->getTTL()
                * 60,

            'user' => [
                'id' => $user->id,

                'name' => $user->name,

                'email' => $user->email,
            ],

            'organizations' => [
                [
                    'id' => $organization->id,

                    'name' => $organization->name,

                    'slug' => $organization->slug,
                ],
            ],
        ], 201);
    }

    public function login(
        LoginRequest $request,
    ): JsonResponse {
        $credentials =
            $request->validated();

        if (
            $token =
            $this
                ->guard()
                ->attempt(
                    $credentials
                )
        ) {
            return $this
                ->respondWithToken(
                    $token
                );
        }

        return response()->json(
            [
                'msg' => 'Usuário e/ou senha inválidos.',
            ],
            403,
        );
    }

    public function me(): JsonResponse
    {
        $user =
            $this->guard()->user();

        return response()->json([
            'user' => $user->toArray(),

            'organizations' => $this
                ->availableOrganizations(
                    $user
                ),
        ]);
    }

    public function logout(): JsonResponse
    {
        $this->guard()->logout();

        return response()->json([
            'msg' => 'Desconectado com sucesso.',
        ]);
    }

    public function refresh(): JsonResponse
    {
        return $this
            ->respondWithToken(
                $this
                    ->guard()
                    ->refresh()
            );
    }

    protected function respondWithToken(
        string $token,
    ): JsonResponse {
        $user =
            $this->guard()->user();

        return response()->json([
            'token' => $token,

            'access_token' => $token,

            'token_type' => 'bearer',

            'expires_in' => $this
                ->guard()
                ->factory()
                ->getTTL()
                * 60,

            'userName' => $user->name,

            'userMail' => $user->email,

            'user' => $user->toArray(),

            'organizations' => $this
                ->availableOrganizations(
                    $user
                ),
        ]);
    }

    private function availableOrganizations(
        User $user,
    ): array {
        return $user
            ->organizations()
            ->where(
                'organizations.status',
                'active',
            )
            ->wherePivot(
                'status',
                'active',
            )
            ->orderBy(
                'organizations.name',
            )
            ->get([
                'organizations.id',
                'organizations.name',
                'organizations.slug',
            ])
            ->map(
                fn ($organization) => [
                    'id' => $organization->id,

                    'name' => $organization->name,

                    'slug' => $organization->slug,
                ]
            )
            ->values()
            ->all();
    }

    protected function guard()
    {
        return Auth::guard(
            'api'
        );
    }
}
