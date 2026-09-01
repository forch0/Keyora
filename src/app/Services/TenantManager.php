<?php

declare(strict_types=1);

namespace App\Services;

class TenantManager
{
    private ?int $currentTenantId = null;

    public function setCurrentTenant(int $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    public function currentTenantId(): ?int
    {
        return $this->currentTenantId;
    }

    public function hasCurrentTenant(): bool
    {
        return $this->currentTenantId !== null;
    }

    public function forgetCurrentTenant(): void
    {
        $this->currentTenantId = null;
    }
}
