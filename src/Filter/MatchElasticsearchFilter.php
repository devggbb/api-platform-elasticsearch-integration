<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

final class MatchElasticsearchFilter extends AbstractElasticsearchFilter
{
    public function filterProperty(string $property, $value, QueryBuilder &$queryBuilder): void
    {
        $queryBuilder->addMust(
            $property,
            $value,
            $this->getProperties()[$property]['fuzziness'] ?? 'AUTO',
            $this->getProperties()[$property]['operator'] ?? 'or');
    }
}