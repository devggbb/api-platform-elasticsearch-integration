<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

final class TermBoolElasticsearchFilter extends AbstractElasticsearchFilter
{
    public function filterProperty(string $property, $value, QueryBuilder &$queryBuilder): void
    {
        $queryBuilder->addFilterTermBool($property, $value);
    }
}