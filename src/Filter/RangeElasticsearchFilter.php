<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

final class RangeElasticsearchFilter extends AbstractElasticsearchFilter
{
    public function filterProperty(string $property, $value, QueryBuilder &$queryBuilder): void
    {
        $queryBuilder->addFilterRange($property, $value['gte'] ?? null, $value['lte'] ?? null);
    }
}