<?php

namespace DocteurKlein\Bundle\CrudBundle\Twig\Extension;

use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;

final class Resource extends \Twig_Extension
{
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function getName()
    {
        return 'docteurklein_crud_resource';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('resource_properties', [$this, 'getResourceProperties']),
            new \Twig_SimpleFunction('resource_values', [$this, 'getResourceValues']),
        ];
    }

    public function getResourceProperties($resource, array $serializationGroups = ['Default'])
    {
        $class = $resource;
        if (is_object($resource)) {
            $class = get_class($resource);
        }
        $metadata = $this->serializer->getMetadataFactory()->getMetadataForClass($class);
        if (!$metadata) {
            return [];
        }

        $strategy = new GroupsExclusionStrategy($serializationGroups);

        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $context->setGroups($serializationGroups);

        return array_map(function ($property) {
            return $property->serializedName ?: $property->name;
        }, array_filter($metadata->propertyMetadata, function ($property) use ($strategy, $context) {
            return !$strategy->shouldSkipProperty($property, $context);
        }));
    }

    public function getResourceValues($resource, array $serializationGroups = ['Default'])
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);
        $context->setGroups($serializationGroups);

        $data = json_decode($this->serializer->serialize($resource, 'json', $context), true);
        unset($data['_links']);

        return $data;
    }
}
