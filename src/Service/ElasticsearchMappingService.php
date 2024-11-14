<?php

declare(strict_types=1);

namespace Ggbb\ApiPlatformElasticsearchIntegrationBundle\Service;

use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Attribute\ElasticsearchEntity;
use Ggbb\ApiPlatformElasticsearchIntegrationBundle\Attribute\ElasticsearchField;
use ReflectionClass;
use ReflectionMethod;

class ElasticsearchMappingService
{
    public function getIndex(mixed $entity): string
    {
        return $this->getAttribute($entity, 'index');
    }

    public function getSettings(mixed $entity): array
    {
        return $this->getAttribute($entity, 'settings');
    }

    public function getMappings(mixed $entity): array
    {
        return $this->getAttribute($entity, 'mappings');
    }

    private function getAttribute(mixed $entity, string $attributeName): string|array
    {
        $reflectionClass = new ReflectionClass($entity);
        $attributes = $reflectionClass->getAttributes(ElasticsearchEntity::class);

        if (!empty($attributes)) {
            $attribute = $attributes[0]->newInstance();
            return $attribute->{$attributeName} ?? [];
        }

        throw new \RuntimeException('Index not defined in ElasticsearchEntity');
    }

    public function hasElasticsearchEntity(mixed $entity): bool
    {
        $reflectionClass = new ReflectionClass($entity);
        return !empty($reflectionClass->getAttributes(ElasticsearchEntity::class));
    }

    public function getElasticsearchFields(mixed $entity): array
    {
        $reflectionClass = new ReflectionClass($entity);
        $fields = [];

        foreach ($reflectionClass->getProperties() as $property) {
            $attributes = $property->getAttributes(ElasticsearchField::class);
            if (!empty($attributes)) {
                $attribute = $attributes[0]->newInstance();
                $name = $attribute->name ?? $property->getName();
                $fields[$property->getName()] = $name;
            }
        }

        return $fields;
    }

    public function getElasticsearchMethods(mixed $entity): array
    {
        $reflectionClass = new ReflectionClass($entity);
        $methods = [];

        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(ElasticsearchField::class);
            if (!empty($attributes)) {
                $attribute = $attributes[0]->newInstance();
                $name = $attribute->name ?? $method->getName();
                $methods[$method->getName()] = $name;
            }
        }

        return $methods;
    }

    public function convertEntityToArray(object $entity): array
    {
        $reflectionClass = new ReflectionClass($entity);
        $data = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if ($this->hasElasticsearchFieldAttribute($property, $fieldName)) {
                $data[$fieldName] = $property->getValue($entity);
            }
        }
        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->hasElasticsearchFieldAttribute($method, $fieldName)) {
                $data[$fieldName] = $method->invoke($entity);
            }
        }

        return $data;
    }

    private function hasElasticsearchFieldAttribute($reflection, &$fieldName): bool
    {
        $attributes = $reflection->getAttributes(ElasticsearchField::class);
        if (!empty($attributes)) {
            $attribute = $attributes[0]->newInstance();
            $fieldName = $attribute->name ?? $reflection->getName();
            return true;
        }
        return false;
    }

    public function serializeEntity(object $entity): array
    {
        $fields = $this->getElasticsearchFields($entity);
        $methods = $this->getElasticsearchMethods($entity);
        $data = [];

        foreach ($fields as $property => $name) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($entity, $getter)) {
                $data[$name] = $entity->$getter();
            }
        }

        foreach ($methods as $property => $name) {
            if (method_exists($entity, $property)) {
                $data[$name] = $entity->$property();
            }
        }

        return $data;
    }
}