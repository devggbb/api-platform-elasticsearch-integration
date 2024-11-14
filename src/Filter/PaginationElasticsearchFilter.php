<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticSearchRequestInfoService;

final class PaginationElasticsearchFilter extends AbstractElasticsearchFilter
{
    public function __construct(
        protected ElasticSearchRequestInfoService $requestInfoService,
    )
    {
    }

    public function filterProperty(string $property, $value, QueryBuilder &$queryBuilder): void
    {
        $queryBuilder->setPagination($value);
        $this->requestInfoService->setFirstResult($queryBuilder->getQuery()['from']);
    }
}