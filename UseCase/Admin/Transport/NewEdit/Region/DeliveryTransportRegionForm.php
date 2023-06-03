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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Region;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeliveryTransportRegionForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* GPS широта:*/

        $builder->add('latitude', TextType::class, ['required' => false, 'attr' => ['data-latitude' => 'true']]);

        $builder->get('latitude')->addModelTransformer(
            new CallbackTransformer(
                function ($gps) {
                    return $gps instanceof GpsLatitude ? $gps->getValue() : $gps;
                },
                function ($gps) {
                    return new GpsLatitude($gps);
                }
            )
        );

        /* GPS долгота:*/

        $builder->add('longitude', TextType::class, ['required' => false, 'attr' => ['data-longitude' => 'true']]);

        $builder->get('longitude')->addModelTransformer(
            new CallbackTransformer(
                function ($gps) {
                    return $gps instanceof GpsLongitude ? $gps->getValue() : $gps;
                },
                function ($gps) {
                    return new GpsLongitude($gps);
                }
            )
        );

        /*
         * Название региона.
         */
        $builder->add('address', TextType::class, ['attr' => ['data-address' => true]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryTransportRegionDTO::class,
        ]);
    }
}
