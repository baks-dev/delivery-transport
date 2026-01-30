<?php
/*
 * Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\Messenger\ProductParameter\Parameters;

use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\DeliveryTransport\Repository\ProductParameter\OnePackageParameterByProductProperties\OnePackageParameterByProductPropertiesInterface;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UpdateMultipleProductsPackageParameterParametersForm extends AbstractType
{
    public function __construct(
        private readonly OnePackageParameterByProductPropertiesInterface $OnePackageParameterByProductPropertiesRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options) : void
	{
        /**
         * Вес, кг.
         */
        $builder->add('weight', NumberType::class, ['attr' => ['min' => 0]]);

        $builder->get('weight')->addModelTransformer(
            new CallbackTransformer(
                function($weight) {
                    return $weight instanceof Kilogram ? $weight->getValue() : $weight;
                },
                function($weight) {
                    return $weight ? new Kilogram($weight) : null;
                }
            )
        );

        /**
         * Длина (Глубина), см
         */
        $builder->add('length', IntegerType::class, ['attr' => ['min' => 1]]);

        /**
         * Ширина, см
         */
        $builder->add('width', IntegerType::class, ['attr' => ['min' => 1]]);

        /**
         * Высота, см
         */
        $builder->add('height', IntegerType::class, ['attr' => ['min' => 1]]);


        /**
         * Машиноместо
         */
        $builder->add('package', IntegerType::class, ['attr' => ['min' => 1]]);


        /** Предзаполнение полей */
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
            /**
             * По полученным на предыдущем этапе параметрам продуктов находим параметры упаковки для первого попавшегося
             * из них (чтобы предзаполнить поля "рекомендуемыми" значениями, соответствующими существующим настройкам
             * для схожих товаров)
             */
            $productData = $event->getForm()->getParent()->getData()->getProduct();
            $packageParameter = $this->OnePackageParameterByProductPropertiesRepository
                ->forOffer($productData->getOffer())
                ->forVariation($productData->getVariation())
                ->forModification($productData->getModification())
                ->findOne();
//dd($packageParameter);
            /** Если с такими свойствами товар или настройка для него не были найдены - делаем return */
            if(false === $packageParameter instanceof DeliveryPackageProductParameter)
            {
                return;
            }


            /** Маппим полученные данные на DTO формы */
            $parametersDTO = $event->getData();
            $packageParameter->getDto($parametersDTO);
        });
	}
	
	public function configureOptions(OptionsResolver $resolver) : void
	{
		$resolver->setDefaults([
			'data_class' => UpdateMultipleProductsPackageParameterParametersDTO::class,
			'method' => 'POST',
             'attr' => ['class' => 'w-100'],
		]);
	}
}