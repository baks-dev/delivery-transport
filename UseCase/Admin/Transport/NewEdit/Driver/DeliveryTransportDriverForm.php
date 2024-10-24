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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Driver;


use BaksDev\Users\Profile\UserProfile\Repository\UserProfileByAuthority\UserProfileByAuthorityInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeliveryTransportDriverForm extends AbstractType
{

    private UserProfileByAuthorityInterface $profileByAuthority;

    public function __construct(UserProfileByAuthorityInterface $profileByAuthority)
    {
        $this->profileByAuthority = $profileByAuthority;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** Все профили пользователя */
        $profiles = $this->profileByAuthority
            ->withRole('ROLE_DELIVERY_PACKAGE')
            ->findAll();

        $builder->add('profile', ChoiceType::class, [
            'choices' => $profiles,
            'choice_value' => function(?UserProfileUid $profile) {
                return $profile?->getValue();
            },
            'choice_label' => function(UserProfileUid $profile) {
                return $profile->getAttr();
            },

            'label' => false,
            'required' => true,
        ]);

        /** Удалить */
        $builder->add(
            'delete',
            ButtonType::class,
            ['label_html' => true]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeliveryTransportDriverDTO::class
        ]);
    }
}