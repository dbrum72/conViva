<?php

namespace App\Support\Tenancy;

use App\Models\Organization;
use LogicException;

class CurrentOrganization
{
    private ?Organization $organization = null;

    public function set(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function get(): Organization
    {
        if ($this->organization === null) {
            throw new LogicException(
                'A organização atual ainda não foi resolvida.'
            );
        }

        return $this->organization;
    }

    public function id(): int
    {
        return (int) $this->get()->getKey();
    }

    public function has(): bool
    {
        return $this->organization !== null;
    }

    public function clear(): void
    {
        $this->organization = null;
    }

    /** Execute a trusted background operation and always restore its caller's context. */
    public function run(Organization $organization, callable $operation): mixed
    {
        $previous = $this->organization;
        $this->set($organization);

        try {
            return $operation();
        } finally {
            $this->organization = $previous;
        }
    }
}
