<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class ElasticsearchField
{
    public function __construct(public ?string $name = null)
    {
    }
}