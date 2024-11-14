<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Interface\IgnoreFieldNameInterface;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder as EsQueryBuilder;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ContainerService;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchConnectService;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchMappingService;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticSearchRequestInfoService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class ElasticsearchFilter extends AbstractFilter
{
    public function __construct(
        protected ElasticSearchRequestInfoService $requestInfoService,
        protected ElasticsearchConnectService     $connectService,
        protected ElasticsearchMappingService     $mappingService,
        protected ContainerService                $containerService,
        protected ManagerRegistry                 $managerRegistry,
        ?LoggerInterface                          $logger = null,
        protected ?array                          $properties = null,
        protected ?NameConverterInterface         $nameConverter = null,
    )
    {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $esQueryBuilder = new EsQueryBuilder($operation->getPaginationItemsPerPage());
        $this->requestInfoService->setPaginationItemsPerPage($operation->getPaginationItemsPerPage());
        $filters = $context['filters'] ?? [];
        foreach ($this->getProperties() as $filterClass => $properties) {
            $filterObject = $this->containerService->getElasticsearchFilter($filterClass);
            $filterObject->setProperties($properties ?? []);

            if ($filterObject instanceof IgnoreFieldNameInterface) {
                $filterObject->filterProperty(null, null, $esQueryBuilder);
                continue;
            }

            if (!array_is_list($properties)) {
                $propertiesKeys = array_keys($properties);
            } else {
                $propertiesKeys = $properties;
            }

            foreach ($filters as $filter => $value) {
                if (!in_array($filter, $propertiesKeys)) {
                    continue;
                }

                $filterObject->filterProperty($filter, $value, $esQueryBuilder);
            }
        }

        $esQueryBuilder->addSourceFields(['id']);
        $response = $this->connectService->getClient()->search([
            'index' => $this->mappingService->getIndex($resourceClass),
            'body' => $esQueryBuilder->build(),
        ]);

        $this->requestInfoService->setIsElasticSearchEnabled(true);
        $this->requestInfoService->setTotalItems($response['hits']['total']['value']);
        $ids = array_column($response['hits']['hits'], '_id');
        if (!empty($ids)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('o.id', $ids));
            $queryBuilder->orderBy('array_position(:arrayId, o.id)', 'ASC');
            $queryBuilder->setParameter('arrayId', $ids);
        } else {
            $queryBuilder->andWhere('1 = 0');
        }
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $property => $ignored) {
            $description["elastic_search_{$property}"] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
                'description' => 'Filter using Elasticsearch',
            ];
        }

        return $description;
    }

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
    }
}