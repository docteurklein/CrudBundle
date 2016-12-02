<?php

namespace DocteurKlein\Bundle\CrudBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use DocteurKlein\Bundle\CrudBundle\Twig\Extension\Resource;

class Auto extends AbstractType implements EventSubscriberInterface
{
    private $resourceHelper;

    public function __construct(Resource $resourceHelper)
    {
        $this->resourceHelper = $resourceHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'with_save_button' => true,
            'groups' => ['Default'],
            'on_embedded' => function (FormInterface $form) {
            },
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventSubscriber($this)
        ;
    }

    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        $options = $form->getConfig()->getOptions();

        $class = $data ? get_class($data) : $options['data_class'];
        $fields = $this->resourceHelper->getResourceProperties($class, $options['groups']);
        foreach ($fields as $property) {
            $form->add($property);
        }

        if ($options['with_save_button']) {
            $form->add('save', SubmitType::class);
        }
        $onEmbedded = $options['on_embedded'];
        $onEmbedded($form);
    }
}
