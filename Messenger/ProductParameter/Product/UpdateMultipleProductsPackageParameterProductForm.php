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

namespace BaksDev\DeliveryTransport\Messenger\ProductParameter\Product;

use BaksDev\Core\Services\Fields\FieldsChoice;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Repository\ModificationFieldsCategoryChoice\ModificationFieldsCategoryChoiceInterface;
use BaksDev\Products\Category\Repository\OfferFieldsCategoryChoice\OfferFieldsCategoryChoiceInterface;
use BaksDev\Products\Category\Repository\VariationFieldsCategoryChoice\VariationFieldsCategoryChoiceInterface;
use BaksDev\Products\Category\Type\Offers\Id\CategoryProductOffersUid;
use BaksDev\Products\Category\Type\Offers\Variation\CategoryProductVariationUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UpdateMultipleProductsPackageParameterProductForm extends AbstractType
{
    public function __construct(
        private readonly CategoryChoiceInterface $categoryChoice,
        private readonly OfferFieldsCategoryChoiceInterface $offerChoice,
        private readonly VariationFieldsCategoryChoiceInterface $variationChoice,
        private readonly ModificationFieldsCategoryChoiceInterface $modificationChoice,
        private readonly FieldsChoice $choice,
    ) {}

	public function buildForm(FormBuilderInterface $builder, array $options) : void
	{
        $builder->add('category', ChoiceType::class, [
            'choices' => $this->categoryChoice->findAll(),
            'choice_value' => function(?CategoryProductUid $category) {
                return $category?->getValue();
            },
            'choice_label' => function(CategoryProductUid $category) {
                return $category->getOptions();
            },
            'label' => false,
        ]);

        $builder->get('category')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event) {
                $category = $event->getForm()->getData();
                $this->formOfferModifier($event->getForm()->getParent(), $category);
            },
        );

        $builder->add('offer', HiddenType::class);

        $builder->add('variation', HiddenType::class);

        $builder->add('modification', HiddenType::class);
	}


    public function formOfferModifier(FormInterface $form, ?CategoryProductUid $category = null): void
    {
        if(null === $category)
        {
            return;
        }

        $offerField = $this->offerChoice
            ->category($category)
            ->findAllCategoryProductOffers();

        if($offerField)
        {
            $inputOffer = $this->choice->getChoice($offerField->getField());

            if($inputOffer)
            {
                $form->add(
                    'offer',
                    $inputOffer->form(),
                    [
                        'label' => $offerField->getOption(),
                        'priority' => 200,
                    ]
                );

                $this->formVariationModifier($form, $offerField);
            }
        }
    }

    public function formVariationModifier(FormInterface $form, CategoryProductOffersUid $offer): void
    {
        /** Множественные варианты торгового предложения */
        $variationField = $this->variationChoice
            ->offer($offer)
            ->findCategoryProductVariation();

        if($variationField)
        {
            $inputVariation = $this->choice->getChoice($variationField->getField());

            if($inputVariation)
            {
                $form->add(
                    'variation',
                    $inputVariation->form(),
                    [
                        'label' => $variationField->getOption(),
                        'priority' => 199,
                    ]
                );

                $this->formModificationModifier($form, $variationField);
            }
        }
    }

    public function formModificationModifier(FormInterface $form, CategoryProductVariationUid $variation): void
    {
        /** Модификации множественных вариантов торгового предложения */
        $modificationField = $this->modificationChoice
            ->variation($variation)
            ->findAllModification();

        if($modificationField)
        {
            $inputModification = $this->choice->getChoice($modificationField->getField());

            if($inputModification)
            {
                $form->add(
                    'modification',
                    $inputModification->form(),
                    [
                        'label' => $modificationField->getOption(),
                        'priority' => 198,
                    ]
                );
            }
        }
    }
	
	public function configureOptions(OptionsResolver $resolver) : void
	{
		$resolver->setDefaults([
			'data_class' => UpdateMultipleProductsPackageParameterProductDTO::class,
			'method' => 'POST',
             'attr' => ['class' => 'w-100'],
		]);
	}
}