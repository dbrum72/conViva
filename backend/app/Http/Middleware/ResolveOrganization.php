<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Support\Tenancy\CurrentOrganization;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveOrganization
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $slug = trim(
            (string) $request->header(
                'X-Tenant',
                '',
            )
        );

        if ($slug === '') {
            return $this->error(
                'O cabeçalho X-Tenant é obrigatório.',
                Response::HTTP_BAD_REQUEST,
            );
        }

        $organization =
            Organization::query()
                ->where(
                    'slug',
                    $slug,
                )
                ->where(
                    'status',
                    'active',
                )
                ->first();

        if ($organization === null) {
            return $this->error(
                'Organização não encontrada.',
                Response::HTTP_NOT_FOUND,
            );
        }

        $user =
            $request->user('api');

        if ($user === null) {
            return $this->error(
                'Não autenticado.',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $hasActiveMembership =
            $organization
                ->users()
                ->whereKey(
                    $user->getKey()
                )
                ->wherePivot(
                    'status',
                    'active',
                )
                ->exists();

        if (! $hasActiveMembership) {
            return $this->error(
                'Usuário não possui acesso à organização informada.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $previousTeamId =
            getPermissionsTeamId();

        $this->currentOrganization->set(
            $organization
        );

        setPermissionsTeamId(
            $organization->id
        );

        $this->clearPermissionRelations(
            $user
        );

        try {
            return $next($request);
        } finally {
            $this->clearPermissionRelations(
                $user
            );

            setPermissionsTeamId(
                $previousTeamId
            );

            $this
                ->currentOrganization
                ->clear();
        }
    }

    private function clearPermissionRelations(
        $user,
    ): void {
        $user->unsetRelation(
            'roles'
        );

        $user->unsetRelation(
            'permissions'
        );
    }

    private function error(
        string $message,
        int $status,
    ): JsonResponse {
        return response()->json(
            [
                'message' => $message,
            ],
            $status,
        );
    }
}
