<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\EventListener;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchConnectService;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service\ElasticsearchMappingService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;

class ElasticsearchListener
{
    public function __construct(
        private ElasticsearchConnectService $esConnect,
        private ElasticsearchMappingService $esMapping,
    )
    {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$this->esMapping->hasElasticsearchEntity($entity)) {
            return;
        }

        $index = $this->esMapping->getIndex($entity);
        $params = [
            'index' => $index,
            'id'    => $entity->getId(),
            'body'  => $this->esMapping->serializeEntity($entity),
        ];

        $this->esConnect->getClient()->index($params);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$this->esMapping->hasElasticsearchEntity($entity)) {
            return;
        }

        $index = $this->esMapping->getIndex($entity);
        $params = [
            'index' => $index,
            'id'    => $entity->getId(),
            'body'  => [
                'doc' => $this->esMapping->serializeEntity($entity),
            ],
        ];

        $this->esConnect->getClient()->update($params);
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$this->esMapping->hasElasticsearchEntity($entity)) {
            return;
        }

        $index = $this->esMapping->getIndex($entity);
        $params = [
            'index' => $index,
            'id'    => $entity->getId(),
        ];

        $this->esConnect->getClient()->delete($params);
    }
}