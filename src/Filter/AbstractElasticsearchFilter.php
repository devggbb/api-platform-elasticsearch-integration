<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

abstract class AbstractElasticsearchFilter
{
    private array $properties = [];
    abstract public function filterProperty(string $property, $value, QueryBuilder &$queryBuilder): void;

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }
}