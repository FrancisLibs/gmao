<?php

namespace App\Form;

use App\Entity\Workorder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class WorkorderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => false,
                'choices'  => [
                    'Curatif' => 1,
                    'Préventif' => 2,
                    'Amélioratif' => 3,
                ],
            ])
            ->add('request', TextType::class, [
                'label' => 'Demande'
            ])
            ->add('implementation', TextType::class, [
                'label' => 'Réalisation'
            ])
            ->add('remark', TextareaType::class, [
                'label' => 'Remarque',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'input' => 'datetime',
                'label' => 'Date début',
                'widget' => 'single_text',
            ])
            ->add('startTime', TimeType::class, [
                'label' => 'Heure début',
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('endDate', DateType::class, [
                'input' => 'datetime',
                'label' => 'Date fin',
                'widget' => 'single_text',
            ])
            ->add('endTime', TimeType::class, [
                'label' => 'Heure fin',
                'widget' => 'single_text',
                'input' => 'datetime',
                'required'  => false,
            ])
            ->add('durationDay', TextType::class, [
                'label' =>  'Jours',
            ])
            ->add('durationHour', TextType::class, [
                'label' =>  'Heures'
            ])
            ->add('durationMinute', TextType::class, [
                'label' =>  'Minutes'
            ])
            ->add('stopTimeHour', TextType::class, [
                'label' => 'Heures',
                'required' => false,
            ])
            ->add('stopTimeMinute', TextType::class, [
                'label' => 'Minutes',
                'required' => false,
            ])
            ->add('operationPrice', NumberType::class, [
                'label' =>  'Cout opération'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WorkOrder::class,
            'organisation' => null,
            'translation_domain' => 'forms'
        ]);
    }
}
