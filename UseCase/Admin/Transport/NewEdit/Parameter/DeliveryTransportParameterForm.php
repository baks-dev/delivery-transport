<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Parameter;

use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeliveryTransportParameterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
         * Грузоподъемность, кг
         */
        $builder->add('carrying', NumberType::class, [
            'attr' => [
                'min' => 1
            ]
        ]);

        $builder->get('carrying')->addModelTransformer(
            new CallbackTransformer(
                function($carrying) {
                    return $carrying instanceof Kilogram ? $carrying->getValue() : $carrying;
                },
                function($carrying) {
                    return $carrying ? new Kilogram($carrying) : null;
                }
            )
        );


        /*
         * Длина (Глубина), см.
         */
        $builder->add('length', IntegerType::class,
            [
                'attr' => [
                    'min' => 1
                ]
            ]
        );

        /*
         *  Ширина, см.
         */
        $builder->add('width', IntegerType::class,
            [
                'attr' => [
                    'min' => 1
                ]
            ]
        );

        /*
         * Высота, см.
         */
        $builder->add('height', IntegerType::class,
            [
                'attr' => [
                    'min' => 1
                ]
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryTransportParameterDTO::class,
        ]);
    }
}
