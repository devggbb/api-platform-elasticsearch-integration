# ApiPlatformElasticsearchIntegration
This library provides the integration of Elasticsearch with the main database (for example, PostgreSQL or MySQL) used in the API Platform and Doctrine. It allows you to perform the necessary queries with a simple setup through the Elasticsearch database, providing all the advantages of Elasticsearch in conjunction with the API Platform and Doctrine.

### The diagram below shows how the data query process works using the Symfony API Platform and Elasticsearch.
<pre>
+--------+       +----------------------------+           +----------------+  
| Client | ----> | Symfony API                | <-------  |  Main DB       |
|        | <---- | Platform (GetCollection)   |           |                |   
+--------+       +----------------------------+           +----------------+ 
                     |                                        |
                     v                                        |
                 +----------------------------+               |
                 | Request to Elasticsearch   |               |  
                 +----------------------------+               |
                     |                                        |
                     +---------- ID's------------------------>|
</pre>


## Installation
Installation from composer
```console
composer require ggbb/api-platform-elasticsearch-integration
```

doctrine.yaml
```yaml
doctrine:
  orm:
    dql:
      string_functions:
        array_position: Ggbb\ApiPlatformElasticsearchIntegrationBundle\DQL\ArrayPositionFunction
```
## Usage examples
### Connecting to an entity in Symfony.
```php
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Provider\ElasticsearchProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: ElasticsearchProvider::class,
        ),
    ],
    paginationEnabled: false,
)]
#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ElasticsearchEntity(
    index: 'post',
    settings: [
        "analysis" => [
            ...
        ]
    ],
    mappings: [
        "properties" => [
            ...
        ]
    ]
)]
class Post
{
    ... // Implementation of the entity
}
```

### Adding filters
```php
#[ApiFilter(ElasticsearchFilter::class, properties: [
    RangeElasticsearchFilter::class => [
        'price',
        'area',
        'floor',
    ],
    SortElasticsearchFilter::class => [
        'created_at' => 'desc',
        'id' => 'desc',
    ],
    PaginationElasticsearchFilter::class => [
        '_page'
    ],
])]
#[ElasticsearchEntity()
class Post
{
    ... // Implementation of the entity
}
```

### Adding the field to the elasticsearch indexing
```php
#[ElasticsearchEntity()
class Post
{
    ... // Implementation of the entity
    
    #[ORM\Column(nullable: true)]
    #[ElasticsearchField]
    private ?int $regionCodeAlt = null;
    
    #[ElasticsearchField(name: 'user')]
    public function getUserId(): ?int
    {
        return $this->getUser()?->getId();
    }
}
```

### Creating a custom filter
```php
<?php

namespace App\Filter;

use App\Service\User\UserService;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\AbstractElasticsearchFilter;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Interface\IgnoreFieldNameInterface;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

final class OrganizationElasticsearchFilter extends AbstractElasticsearchFilter implements IgnoreFieldNameInterface
{
    public function __construct(private UserService $userService)
    {
    }

    public function filterProperty(?string $property, mixed $value, QueryBuilder &$queryBuilder): void
    {
        $organization = $this->userService->getOrganization();
        $queryBuilder->addFilterTerm('organization', (int) $organization->getId());
    }
}
```

```php
#[ApiFilter(ElasticsearchFilter::class, properties: [
    OrganizationElasticsearchFilter::class,
])]
class Post
{
    ... // Implementation of the entity
}
```
### Management commands
This command extracts data from the database, generates an index in Elasticsearch based on the specified fields, first clearing, then creating indexes and filling them with data from the specified entity.
```console
php bin/console elasticsearch:import 'App\Entity\Post' --env=prod --batch-size=150000
```
Mapping output of the entire indexed structure in Elasticsearch.
```console
php bin/console elasticsearch:show-entities
```