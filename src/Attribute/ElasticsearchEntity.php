<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class ElasticsearchEntity
{
    public function __construct(public string $index,
                                public ?array $settings = null,
                                public ?array $mappings = null)
    {
    }
}