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

namespace BaksDev\DeliveryTransport\Messenger\ProductParameter;

use BaksDev\DeliveryTransport\Messenger\ProductParameter\Parameters\UpdateMultipleProductsPackageParameterParametersForm;
use BaksDev\DeliveryTransport\Messenger\ProductParameter\Product\UpdateMultipleProductsPackageParameterProductForm;
use Symfony\Component\Form\Flow\AbstractFlowType;
use Symfony\Component\Form\Flow\FormFlowBuilderInterface;
use Symfony\Component\Form\Flow\Type\FinishFlowType;
use Symfony\Component\Form\Flow\Type\NextFlowType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UpdateMultipleProductsPackageParameterForm extends AbstractFlowType
{
	public function buildFormFlow(FormFlowBuilderInterface $builder, array $options) : void
    {
        $builder->addStep('product', UpdateMultipleProductsPackageParameterProductForm::class);
        $builder->addStep('parameters', UpdateMultipleProductsPackageParameterParametersForm::class);

        /** Далее (форма @see UpdateMultipleProductsPackageParameterParametersForm) */
        $builder->add(
            'product_package_parameters_next',
            NextFlowType::class,
            ['label' => 'Next', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );

        /** Сохранить */
        $builder->add(
            'product_package_parameters',
            FinishFlowType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]
        );
    }
	
	public function configureOptions(OptionsResolver $resolver) : void
	{
		$resolver->setDefaults([
			'data_class' => UpdateMultipleProductsPackageParameterDTO::class,
            'step_property_path' => 'currentStep',
		]);
	}
}