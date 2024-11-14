<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Tests\Filter\Query;

use PHPUnit\Framework\TestCase;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function testConstructorInitializesQueryWithDefaultSize(): void
    {
        $queryBuilder = new QueryBuilder();
        $this->assertEquals(10, $queryBuilder->getQuery()['size']);
    }

    public function testAddMustAddsMatchCondition()
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addMust('name', 'test');

        $expected = [
            'match' => [
                'name' => [
                    'query' => 'test',
                    'fuzziness' => 'AUTO',
                    'operator' => 'or'
                ]
            ]
        ];

        $this->assertContains($expected, $queryBuilder->getQuery()['query']['bool']['must']);
    }

    public function testAddShouldAddsShouldCondition(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addShould('description', 'sample');

        $expected = [
            'match' => [
                'description' => [
                    'query' => 'sample',
                    'fuzziness' => 'AUTO',
                    'operator' => 'or'
                ]
            ]
        ];

        $this->assertContains($expected, $queryBuilder->getQuery()['query']['bool']['should']);
    }

    public function testAddFilterTermAddsFilterCondition(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addFilterTerm('status', 'active');

        $expected = [
            'term' => [
                'status.keyword' => 'active'
            ]
        ];

        $this->assertContains($expected, $queryBuilder->getQuery()['query']['bool']['filter']);
    }

    public function testAddFilterTermBoolAddsBooleanFilterCondition(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addFilterTermBool('is_active', 'true');

        $expected = [
            'term' => [
                'is_active' => true
            ]
        ];

        $this->assertContains($expected, $queryBuilder->getQuery()['query']['bool']['filter']);
    }

    public function testAddFilterRangeAddsRangeCondition(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addFilterRange('age', '18', '30');

        $expected = [
            'range' => [
                'age' => [
                    'gte' => '18',
                    'lte' => '30'
                ]
            ]
        ];

        $this->assertContains($expected, $queryBuilder->getQuery()['query']['bool']['filter']);
    }

    public function testAddSortAddsSorting(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addSort('date', 'asc');

        $expected = [
            'date' => [
                'order' => 'asc'
            ]
        ];

        $this->assertContains($expected, $queryBuilder->getQuery()['sort']);
    }

    public function testSetPaginationSetsFromOffset(): void
    {
        $queryBuilder = new QueryBuilder(10);
        $queryBuilder->setPagination(3);

        $this->assertEquals(20, $queryBuilder->getQuery()['from']);
    }

    public function testSetSizeSetsQuerySize(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setSize(50);

        $this->assertEquals(50, $queryBuilder->getQuery()['size']);
    }

    public function testAddSourceFieldsAddsSourceFields(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->addSourceFields(['field1', 'field2']);

        $this->assertEquals(['field1', 'field2'], $queryBuilder->getQuery()['_source']);
    }

    public function testCustomQueryMergesCustomQuery(): void
    {
        $queryBuilder = new QueryBuilder();
        $customQuery = [
            'term' => [
                'field' => 'value'
            ]
        ];
        $queryBuilder->customQuery($customQuery);

        $this->assertArrayHasKey('term', $queryBuilder->getQuery()['query']);
        $this->assertEquals('value', $queryBuilder->getQuery()['query']['term']['field']);
    }

    public function testBuildReturnsQueryArray(): void
    {
        $queryBuilder = new QueryBuilder();
        $query = $queryBuilder->build();

        $this->assertIsArray($query);
        $this->assertArrayHasKey('query', $query);
    }
}