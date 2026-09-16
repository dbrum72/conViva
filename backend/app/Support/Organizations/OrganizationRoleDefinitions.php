<?php

namespace App\Support\Organizations;

final class OrganizationRoleDefinitions
{
    public const SUPER_ADMIN = 'responsavel';

    public const ADMINISTRATOR = 'responsavel';

    public static function permissions(): array
    {
        return ['recipients.view', 'recipients.create', 'recipients.update', 'recipients.delete', 'care.write', 'organization-members.view', 'organization-members.invite', 'organization-members.update-role', 'organization-members.update-status', 'roles.view', 'roles.update'];
    }

    public static function definitions(): array
    {
        return [
            'responsavel' => ['description' => 'Organiza os próprios registros e delibera sobre cuidados compartilhados', 'permissions' => array_values(array_diff(self::permissions(), ['roles.update']))],
            'cuidador' => ['description' => 'Terceiro contratado ou autorizado a executar serviços nas áreas concedidas', 'permissions' => ['recipients.view', 'care.write']],
            'observador' => ['description' => 'Acompanha somente as áreas autorizadas, sem alterações', 'permissions' => ['recipients.view']]];
    }
}
