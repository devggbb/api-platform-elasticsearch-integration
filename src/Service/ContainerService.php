<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Attribute\ElasticsearchEntity;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Attribute\ElasticsearchField;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\AbstractElasticsearchFilter;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\ElasticsearchFilter;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\SortElasticsearchFilter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionMethod;

class ContainerService
{
    public function __construct(
        protected ContainerInterface $container,
    )
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getElasticsearchFilter(string $className): AbstractElasticsearchFilter
    {
        return $this->container->get($className);
    }
}