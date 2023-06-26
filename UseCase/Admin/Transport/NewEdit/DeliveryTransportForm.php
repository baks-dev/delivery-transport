<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit;

use BaksDev\Contacts\Region\Repository\WarehouseChoice\WarehouseChoiceInterface;
use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeliveryTransportForm extends AbstractType
{
    private WarehouseChoiceInterface $warehouseChoice;

    public function __construct(
        WarehouseChoiceInterface $warehouseChoice,
    ) {
        $this->warehouseChoice = $warehouseChoice;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
         * Регистрационный номер
         */
        $builder->add('number', TextType::class);

        /*
         * Флаг активности.
         */
        $builder->add('active', CheckboxType::class, ['required' => false]);

        /*
         * Идентификатор склада, за которым закреплен транспорт (Константа склада).
         */
        $builder
            ->add('warehouse', ChoiceType::class, [
                'choices' => $this->warehouseChoice->fetchAllWarehouse(),
                'choice_value' => function (?ContactsRegionCallConst $warehouse) {
                    return $warehouse?->getValue();
                },
                'choice_label' => function (ContactsRegionCallConst $warehouse) {
                    return $warehouse->getAttr();
                },

                'label' => false,
                'required' => true,
            ]);

        /*
        * Настройки локали
        */
        $builder->add('translate', CollectionType::class, [
            'entry_type' => Trans\DeliveryTransportTransForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__delivery_translate__',
        ]);

        /*
         * Параметры автомобиля.
         */
        $builder->add('parameter', Parameter\DeliveryTransportParameterForm::class, ['label' => false]);

        /*
         * Регион обслуживания.
         */
        $builder->add('region', Region\DeliveryTransportRegionForm::class, ['label' => false]);

        /* Сохранить */
        $builder->add(
            'delivery_transport',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryTransportDTO::class,
        ]);
    }
}
