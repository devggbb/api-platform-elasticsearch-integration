<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ElasticsearchConnectService
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts([$_ENV['ELASTICSEARCH_HOSTS']])->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}