<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Provider;

use ApiPlatform\Doctrine\Orm\Extension\PaginationExtension;
use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\LinksHandlerTrait;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticSearchRequestInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;

class ElasticsearchProvider implements ProviderInterface
{
    use LinksHandlerTrait;

    /**
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(
        private readonly CollectionProvider                 $collectionProvider,
        private ElasticSearchRequestInfoService    $requestInfoService,
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ManagerRegistry           $managerRegistry,
        private readonly Pagination $pagination,
        private readonly EntityManagerInterface $entityManager,
        private readonly iterable                  $collectionExtensions = [],
        ?ContainerInterface                        $handleLinksLocator = null,
    )
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->handleLinksLocator = $handleLinksLocator;
    }

    /**
     * @throws QueryException
     * @throws \ReflectionException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var Paginator $paginator */
        $paginator = $this->getResult($operation, $uriVariables, $context);

        return $paginator;
    }

    private function getResult(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $entityClass = $operation->getClass();
        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $entityClass = $options->getEntityClass();
        }

        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        $repository = $manager->getRepository($entityClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, ['entityClass' => $entityClass, 'operation' => $operation] + $context);
        } else {
            $this->handleLinks($queryBuilder, $uriVariables, $queryNameGenerator, $context, $entityClass, $operation);
        }

        $collectionExtensions = $this->getProperty($this->collectionProvider, 'collectionExtensions');

        foreach ($collectionExtensions as $extension) {
            if (!($extension instanceof PaginationExtension)) {
                $extension->applyToCollection($queryBuilder, $queryNameGenerator, $entityClass, $operation, $context);
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws \ReflectionException
     */
    private function getProperty(mixed $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }

}