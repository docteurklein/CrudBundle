<?php

namespace DocteurKlein\Bundle\CrudBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Hateoas\Configuration\Route;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Hateoas\Configuration\Relation;
use Porpaginas\Pager;
use Hateoas\Representation\PaginatedRepresentation;
use Hateoas\Representation\CollectionRepresentation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class CrudController
{
    private $doctrine;
    private $viewHandler;
    private $formFactory;
    private $router;

    public function __construct(ManagerRegistry $doctrine, ViewHandlerInterface $viewHandler, FormFactoryInterface $formFactory, UrlGeneratorInterface $router)
    {
        $this->doctrine = $doctrine;
        $this->viewHandler = $viewHandler;
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->propertyAccessor = new PropertyAccessor;
    }

    public function indexAction(Request $request)
    {
        $result = $this->getRepository(
            $request->attributes->get('class'),
            $request->attributes->get('object_manager')
        )
            ->paginate($request->query->get('search'))
        ;
        $pager = Pager::fromResult($result, $request->get('page', 1), 20);
        $page = $pager->getIterator();
        $representation = new PaginatedRepresentation(
            $collection = new CollectionRepresentation(
                $page, null, null, null, null, [
                    new Relation('new', new Route($request->attributes->get('post_route'))),
                ]
            ),
            $request->attributes->get('_route'), [],
            $page->getCurrentPage(),
            20,
            $pager->getNumberOfPages(),
            'page',
            'limit',
            false,
            $page->totalCount()
        );

        return $this->viewHandler->handle(View::create($representation)
            ->setTemplate($request->attributes->get('template'))
            ->setTemplateData([
                'collection' => $collection,
                'resources' => $pager,
                'class' => $this->resolveClassAlias($request),
                'base_layout' => $request->attributes->get('base_layout'),
            ])
        );
    }

    public function showAction(Request $request, $id)
    {
        $resource = $this->getOr404($request, $id);

        return View::create($resource)
            ->setTemplate($request->attributes->get('template'))
            ->setTemplateData([
                'resource' => $resource,
                'base_layout' => $request->attributes->get('base_layout'),
            ])
        ;
    }

    public function createAction(Request $request)
    {
        $class = $this->resolveClassAlias($request);
        $reflect = new \ReflectionClass($class);
        $object = $reflect->newInstance();

        return $this->handle($request, $object, 'POST');
    }

    public function updateAction(Request $request, $id)
    {
        $object = $this->getOr404($request, $id);

        return $this->handle($request, $object, 'PUT');
    }

    public function deleteAction(Request $request, $id)
    {
        $object = $this->getOr404($request, $id);

        $om = $this->getObjectManager($request->attributes->get('object_manager'));
        $om->remove($object);
        $om->flush();
        $this->notify($request, 'success');

        return View::createRouteRedirect($request->attributes->get('redirect_route'));
    }

    private function createForm(Request $request, $object, $method)
    {
        return $this->formFactory->create($request->attributes->get('form_type'), $object, [
            'action' => $this->router->generate($request->attributes->get('post_route'), [
                'id' => $this->propertyAccessor->getValue($object, 'id')
            ]),
            'method' => $method,
            'data_class' => $this->resolveClassAlias($request),
        ]);
    }

    private function handle(Request $request, $object, $method)
    {
        $form = $this->createForm($request, $object, $method);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->save($form, $object, $request->attributes->get('object_manager'));
            $this->notify($request, 'success');

            return View::createRouteRedirect($request->attributes->get('redirect_route'), [
                'id' => $this->propertyAccessor->getValue($object, 'id')
            ])
                ->setData($object)
                ->setTemplate($request->attributes->get('template'))
            ;
        }

        return $this->viewHandler->handle(View::create($form->createView())
            ->setTemplate($request->attributes->get('template'))
            ->setTemplateData([
                'form' => $form->createView(),
                'base_layout' => $request->attributes->get('base_layout'),
            ])
        );
    }

    private function getOr404(Request $request, $id)
    {
        return $this->getRepository(
            $request->attributes->get('class'),
            $request->attributes->get('object_manager')
        )->getOrThrow($id, new NotFoundHttpException());
    }

    private function getRepository($class, $name = null)
    {
        return $this->doctrine->getRepository($class, $name);
    }

    private function getObjectManager($name)
    {
        return $this->doctrine->getManager($name);
    }

    private function save(FormInterface $form, $object, $omName = null)
    {
        $om = $this->getObjectManager($omName);
        $om->persist($object);
        $om->flush();
    }

    private function notify(Request $request, $type, $message = null)
    {
        $format = $request->attributes->get('_format');
        $route = $request->attributes->get('_route');
        $message = $message ?: "$route.$type";
        if ($this->viewHandler->isFormatTemplating($format)) {
            $request->getSession()->getFlashBag()->add($type, $message);
        }
    }

    private function resolveClassAlias(Request $request)
    {
        $om = $this->getObjectManager($request->attributes->get('object_manager'));

        return $om->getClassMetadata($request->attributes->get('class'))->getName();
    }
}
