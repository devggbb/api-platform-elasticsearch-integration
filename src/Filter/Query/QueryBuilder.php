<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Filter\Query;

class QueryBuilder
{
    private array $query;

    public function __construct(int $size = 10)
    {
        $this->query = [
            'query' => [
                'bool' => [
                    'must' => [],
                    'should' => [],
                    'filter' => []
                ]
            ],
            'sort' => [],
            'from' => 0,
            'size' => $size,
            '_source' => []
        ];
    }

    public function addMust(string $field, string $value, string $fuzziness = 'AUTO', string $operator = 'or'): self
    {
        $this->query['query']['bool']['must'][] = [
            'match' => [
                $field => [
                    "query" => $value,
                    "fuzziness" => $fuzziness,
                    "operator" => $operator
                ]
            ]
        ];
        return $this;
    }

    public function addShould(string $field, string $value, string $fuzziness = 'AUTO', string $operator = 'or'): self
    {
        $this->query['query']['bool']['should'][] = [
            'match' => [
                $field => [
                    "query" => $value,
                    "fuzziness" => $fuzziness,
                    "operator" => $operator
                ]
            ]
        ];
        return $this;
    }

    public function addFilterTerm(string $field, string $value): self
    {
        $key = is_numeric($value) ? $field : "$field.keyword";

        $this->query['query']['bool']['filter'][] = [
            'term' => [
                $key => $value
            ]
        ];
        return $this;
    }

    public function addFilterTermBool(string $field, string $value): self
    {
        $this->query['query']['bool']['filter'][] = [
            'term' => [
                $field => $value == 'true'
            ]
        ];
        return $this;
    }

    public function addFilterRange(string $field, ?string $gte = null, ?string $lte = null): self
    {
        $range = [];
        if ($gte) {
            $range['gte'] = $gte;
        }
        if ($lte) {
            $range['lte'] = $lte;
        }

        $this->query['query']['bool']['filter'][] = [
            'range' => [
                $field => $range
            ]
        ];
        return $this;
    }

    public function addSort(string $field, string $order = 'desc'): self
    {
        $this->query['sort'][] = [
            $field => ['order' => $order]
        ];
        return $this;
    }

    public function setPagination(int $pageNumber): self
    {
        $this->query['from'] = ($pageNumber - 1) * $this->query['size'];
        return $this;
    }

    public function setSize(int $size): self
    {
        $this->query['size'] = $size;
        return $this;
    }

    public function addSourceFields(array|string $fields): self
    {
        if (is_array($fields)) {
            $this->query['_source'] = array_merge($this->query['_source'], $fields);
        } else {
            $this->query['_source'][] = $fields;
        }
        return $this;
    }

    public function customQuery(array $customQuery): self
    {
        $this->query['query'] = array_merge_recursive($this->query['query'] ?? [], $customQuery);
        return $this;
    }

    public function customSort(array $customQuery, bool $clean = false): self
    {
        if ($clean) {
            $this->query['sort'] = $customQuery;
            return $this;
        }

        $this->query['sort'] = array_merge_recursive($this->query['sort'] ?? [], $customQuery);
        return $this;
    }

    public function build(): array
    {
        return $this->query;
    }

    public function getQuery(): array
    {
        return $this->query;
    }
}