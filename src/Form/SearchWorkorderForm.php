<?php

namespace App\Form;

use App\Entity\Workorder;
use App\Data\SearchWorkorder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class SearchWorkorderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('status', ChoiceType::class, [
            //     'label'     => false,
            //     'required'  => false,
            //     'placeholder' => 'Status...',
            //     'choices'   => [
            //         'en cours' => Workorder::EN_COURS ,
            //         'cloturé' => Workorder::CLOTURE
            //     ],
            // ])

            // ->add('createdAt', DateType::class, [
            //     'label'     => false,
            //     'required'  => false,
            //     'widget'    => 'single_text', 
            //     'attr'      => ['placeholder' => 'Date...']
            // ])

            ->add('machine', TextType::class, [
                'label'     => false,
                'required'  => false,
                'attr'      => ['placeholder' => 'Machine...']
            ])

            ->add('user', TextType::class, [
                'label'     => false,
                'required'  => false,
                'attr'      => ['placeholder' => 'Technicien...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SearchWorkorder::class,
            'method' => 'GET',
            'csrf_protection' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}