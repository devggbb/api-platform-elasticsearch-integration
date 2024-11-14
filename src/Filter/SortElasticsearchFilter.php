<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Interface\IgnoreFieldNameInterface;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

final class SortElasticsearchFilter extends AbstractElasticsearchFilter implements IgnoreFieldNameInterface
{
    public function filterProperty(?string $property, mixed $value, QueryBuilder &$queryBuilder): void
    {
        foreach ($this->getProperties() as $field => $value) {
            $queryBuilder->addSort($field, $value);
        }
    }
}