<?php

namespace DocteurKlein\Bundle\CrudBundle\Routing;

use Symfony\Component\Config\Loader\Loader as BaseLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use DocteurKlein\Bundle\CrudBundle\Form\Type;

final class Loader extends BaseLoader
{
    public function supports($resource, $type = null)
    {
        return 'docteurklein.crud' === $type;
    }

    public function load($resource, $type = null)
    {
        if (false === $config = parse_url('crud://'.$resource)) {
            throw new \InvalidArgumentException($resource);
        }
        $routes = new RouteCollection();
        $routePrefix = $config['host'];
        if (!empty($config['user'])) {
            $routePrefix = "${config['user']}_${config['host']}";
        }
        $pathPrefix = $config['host'];
        @parse_str(@$config['query'], $params);
        $params = array_merge([
            'controllers' => 'docteurklein.crud.controller',
            'templates' => 'CrudBundle:',
            'base_layout' => 'CrudBundle::layout.html.twig',
            'form_type' => Type\Auto::class,
            'object_manager' => 'default',
            'requirements' => ['id' => '\d+'],
        ], $params ?: []);

        $routes->add("${routePrefix}_list", (new Route($pathPrefix))
            ->setDefaults([
                '_controller' => "${params['controllers']}:indexAction",
                'template' => "${params['templates']}:index.html.twig",
                'base_layout' => $params['base_layout'],
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
                'post_route' => "${routePrefix}_new",
            ])
            ->setRequirements([])
            ->setMethods(['GET'])
        );

        $routes->add("${routePrefix}_new", (new Route("$pathPrefix/new"))
            ->setDefaults([
                '_controller' => "${params['controllers']}:createAction",
                'template' => "${params['templates']}:create.html.twig",
                'base_layout' => $params['base_layout'],
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
                'form_type' => $params['form_type'],
                'post_route' => "${routePrefix}_create",
            ])
            ->setRequirements([])
            ->setMethods(['GET'])
        );

        $routes->add("${routePrefix}_create", (new Route($pathPrefix))
            ->setDefaults([
                '_controller' => "${params['controllers']}:createAction",
                'template' => "${params['templates']}:create.html.twig",
                'base_layout' => $params['base_layout'],
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
                'form_type' => $params['form_type'],
                'post_route' => "${routePrefix}_create",
                'redirect_route' => "${routePrefix}_show",
            ])
            ->setRequirements([])
            ->setMethods(['POST'])
        );

        $routes->add("${routePrefix}_show", (new Route("$pathPrefix/{id}"))
            ->setDefaults([
                '_controller' => "${params['controllers']}:showAction",
                'template' => "${params['templates']}:show.html.twig",
                'base_layout' => $params['base_layout'],
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
            ])
            ->setRequirements([])
            ->setMethods(['GET'])
        );

        $routes->add("${routePrefix}_edit", (new Route("$pathPrefix/{id}/edit"))
            ->setDefaults([
                '_controller' => "${params['controllers']}:updateAction",
                'template' => "${params['templates']}:update.html.twig",
                'base_layout' => $params['base_layout'],
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
                'form_type' => $params['form_type'],
                'post_route' => "${routePrefix}_update",
                'redirect_route' => "${routePrefix}_show",
                '_format' => 'html',
            ])
            ->setRequirements($params['requirements'])
            ->setMethods(['GET'])
        );

        $routes->add("${routePrefix}_update", (new Route("$pathPrefix/{id}"))
            ->setDefaults([
                '_controller' => "${params['controllers']}:updateAction",
                'template' => "${params['templates']}:update.html.twig",
                'base_layout' => $params['base_layout'],
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
                'form_type' => $params['form_type'],
                'post_route' => "${routePrefix}_update",
                'redirect_route' => "${routePrefix}_show",
                '_format' => 'html',
            ])
            ->setRequirements($params['requirements'])
            ->setMethods(['PUT'])
        );

        $routes->add("${routePrefix}_delete", (new Route("$pathPrefix/{id}"))
            ->setDefaults([
                '_controller' => "${params['controllers']}:deleteAction",
                'class' => $params['class'],
                'object_manager' => $params['object_manager'],
                'redirect_route' => "${routePrefix}_list",
            ])
            ->setRequirements($params['requirements'])
            ->setMethods(['DELETE'])
        );

        return $routes;
    }
}
