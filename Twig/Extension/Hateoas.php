<?php

namespace DocteurKlein\Bundle\CrudBundle\Twig\Extension;

use Hateoas\Factory\LinkFactory;
use Hateoas\Configuration\RelationsRepository;
use Hateoas\Configuration\Relation;

final class Hateoas extends \Twig_Extension
{
    private $linkFactory;

    private $relationsRepository;

    public function __construct(LinkFactory $linkFactory, RelationsRepository $relationsRepository)
    {
        $this->linkFactory = $linkFactory;
        $this->relationsRepository = $relationsRepository;
    }

    public function getName()
    {
        return 'docteurklein_crud_hateoas';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('links_hrefs', [$this, 'getLinksHrefs']),
            new \Twig_SimpleFunction('links', [$this, 'getLinks']),
            new \Twig_SimpleFunction('link', [$this, 'getLink']),
            new \Twig_SimpleFunction('has_link', [$this, 'hasLink']),
        ];
    }

    public function getLinks($object, $absolute = false)
    {
        $links = [];
        foreach ($this->relationsRepository->getRelations($object) as $relation) {
            $relation = $this->patchAbsolute($relation, $absolute);

            if (null !== $link = $this->linkFactory->createLink($object, $relation)) {
                $links[$link->getRel()] = $link;
            }
        }

        return $links;
    }

    public function getLinksHrefs($object, $absolute = false)
    {
        $links = [];
        foreach ($this->getLinks($object, $absolute) as $rel => $link) {
            $links[$rel] = $link->getHref();
        }

        return $links;
    }

    public function getLink($object, $rel, $absolute = false)
    {
        foreach ($this->relationsRepository->getRelations($object) as $relation) {
            if ($rel === $relation->getName()) {
                $relation = $this->patchAbsolute($relation, $absolute);

                if (null !== $link = $this->linkFactory->createLink($object, $relation)) {
                    return $link;
                }
            }
        }
    }

    public function hasLink($object, $rel)
    {
        return null !== $this->getLink($object, $rel);
    }

    private function patchAbsolute(Relation $relation, $absolute)
    {
        $href = $relation->getHref();

        if ($href instanceof Route) {
            $href = new Route(
                $href->getName(),
                $href->getParameters(),
                $absolute,
                $href->getGenerator()
            );
        }

        return new Relation(
            $relation->getName(),
            $href,
            $relation->getEmbedded(),
            $relation->getAttributes(),
            $relation->getExclusion()
        );
    }
}
