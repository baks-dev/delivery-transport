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

namespace BaksDev\DeliveryTransport\Controller\Admin\ProductParameter;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\Messenger\ProductParameter\UpdateMultipleProductsPackageParametersDispatcher;
use BaksDev\DeliveryTransport\Messenger\ProductParameter\UpdateMultipleProductsPackageParameterDTO;
use BaksDev\DeliveryTransport\Messenger\ProductParameter\UpdateMultipleProductsPackageParameterForm;
use Symfony\Component\Form\Flow\FormFlowTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
#[RoleSecurity('ROLE_DELIVERY_PACKAGE_PARAMETER_EDIT')]
final class EditMultipleController extends AbstractController
{
    #[Route('/admin/delivery/package/edit/parameters', name: 'admin.parameter.multiple.edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        UpdateMultipleProductsPackageParametersDispatcher $UpdateMultipleProductsPackageParametersDispatcher
    ): Response
    {
        $updateMultipleProductsPackageParametersDTO = new UpdateMultipleProductsPackageParameterDTO();

        // Форма
        $form = $this
            ->createForm(
                UpdateMultipleProductsPackageParameterForm::class,
                $updateMultipleProductsPackageParametersDTO,
                ['action' => $this->generateUrl('delivery-transport:admin.parameter.multiple.edit')]
            )
            ->handleRequest($request);


        /**
         * Чтобы при каждом новом открытии формы рендерить ее с самого начала - сбрасываем ее
         * @var FormFlowTypeInterface $form
         */
        iF(
            'GET' === $request->getMethod() &&
            $updateMultipleProductsPackageParametersDTO->currentStep !== $form->getCursor()->getCurrentStep())
        {
            $form->reset();

            $form = $this->createForm(
                UpdateMultipleProductsPackageParameterForm::class,
                $updateMultipleProductsPackageParametersDTO,
                ['action' => $this->generateUrl('delivery-transport:admin.parameter.multiple.edit')]
            );
        }

        if($form->isSubmitted() && $form->isValid() && $form->isFinished())
        {
            $handle = $UpdateMultipleProductsPackageParametersDispatcher($form->getData());

            $this->addFlash
            (
                'page.edit',
                'success.multiple.edit',
                'delivery-transport.parameter',
                $handle
            );

            return $this->redirectToRoute('delivery-transport:admin.parameter.index');
        }

        return $this->render(['form' => $form->getStepForm()->createView()]);
    }
}