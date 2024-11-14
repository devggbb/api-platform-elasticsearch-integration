<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Serializer\AbstractCollectionNormalizer;
use ApiPlatform\Util\IriHelper;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticSearchRequestInfoService;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class ElasticSearchJsonApiCollectionNormalizer extends AbstractCollectionNormalizer
{
    public const FORMAT = 'jsonapi';

    public function __construct(ResourceClassResolverInterface|LegacyResourceClassResolverInterface $resourceClassResolver,
                                ResourceMetadataCollectionFactoryInterface                          $resourceMetadataFactory,
                                private ElasticSearchRequestInfoService                             $requestInfoService,
    )
    {
        parent::__construct($resourceClassResolver, '_page', $resourceMetadataFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPaginationData($object, array $context = []): array
    {
        [$paginator, $paginated, $currentPage, $itemsPerPage, $lastPage, $pageTotalItems, $totalItems] = $this->getPaginationConfig($object, $context);
        $parsed = IriHelper::parseIri($context['uri'] ?? '/', $this->pageParameterName);

        $operation = $context['operation'] ?? $this->getOperation($context);
        $urlGenerationStrategy = $operation->getUrlGenerationStrategy();

        $data = [
            'links' => [
                'self' => IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $paginated ? $currentPage : null, $urlGenerationStrategy),
            ],
        ];

        if ($paginated) {
            if (null !== $lastPage) {
                $data['links']['first'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, 1., $urlGenerationStrategy);
                $data['links']['last'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $lastPage, $urlGenerationStrategy);
            }

            if (1. !== $currentPage) {
                $data['links']['prev'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage - 1., $urlGenerationStrategy);
            }

            if (null !== $lastPage && $currentPage !== $lastPage || null === $lastPage && $pageTotalItems >= $itemsPerPage) {
                $data['links']['next'] = IriHelper::createIri($parsed['parts'], $parsed['parameters'], $this->pageParameterName, $currentPage + 1., $urlGenerationStrategy);
            }
        }

        if ($this->requestInfoService->isElasticSearchEnabled()) {
            $data['meta']['totalItems'] = $this->requestInfoService->getTotalItems() ?? 0;
            $data['meta']['itemsPerPage'] = $this->requestInfoService->getPaginationItemsPerPage();
            $data['meta']['currentPage'] = floor($this->requestInfoService->getFirstResult() / $this->requestInfoService->getPaginationItemsPerPage()) + 1.;
            $this->requestInfoService->setIsElasticSearchEnabled(false);
        } else {
            if (null !== $totalItems) {
                $data['meta']['totalItems'] = $totalItems;
            }

            if ($paginator) {
                $data['meta']['itemsPerPage'] = (int)$itemsPerPage;
                $data['meta']['currentPage'] = (int)$currentPage;
            }
        }

        return $data;
    }


    /**
     * {@inheritdoc}
     *
     * @throws UnexpectedValueException
     */
    protected function getItemsData($object, ?string $format = null, array $context = []): array
    {
        $data = [
            'data' => [],
        ];

        foreach ($object as $obj) {
            $item = $this->normalizer->normalize($obj, $format, $context);
            if (!\is_array($item)) {
                throw new UnexpectedValueException('Expected item to be an array');
            }

            if (!isset($item['data'])) {
                throw new UnexpectedValueException('The JSON API document must contain a "data" key.');
            }

            $data['data'][] = $item['data'];

            if (isset($item['included'])) {
                $data['included'] = array_values(array_unique(array_merge($data['included'] ?? [], $item['included']), \SORT_REGULAR));
            }
        }

        return $data;
    }
}
