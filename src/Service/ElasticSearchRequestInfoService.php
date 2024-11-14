<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service;

class ElasticSearchRequestInfoService
{
    private $isElasticSearchEnabled = false;
    private $totalItems = 0;
    private $firstResult = 1;
    private $paginationItemsPerPage = 1;

    public function isElasticSearchEnabled(): bool
    {
        return $this->isElasticSearchEnabled;
    }

    public function setIsElasticSearchEnabled(bool $isElasticSearchEnabled): void
    {
        $this->isElasticSearchEnabled = $isElasticSearchEnabled;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
    }

    public function getFirstResult(): int
    {
        return $this->firstResult;
    }

    public function setFirstResult(int $firstResult): void
    {
        $this->firstResult = $firstResult;
    }

    public function getPaginationItemsPerPage(): int
    {
        return $this->paginationItemsPerPage;
    }

    public function setPaginationItemsPerPage(int $paginationItemsPerPage): void
    {
        $this->paginationItemsPerPage = $paginationItemsPerPage;
    }
}