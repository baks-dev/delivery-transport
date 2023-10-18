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

namespace BaksDev\DeliveryTransport\Controller\Admin\Transport;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportForm;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_DELIVERY_TRANSPORT_EDIT')]
final class EditController extends AbstractController
{
    #[Route('/admin/delivery/transport/edit/{id}', name: 'admin.transport.newedit.edit', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        #[MapEntity] DeliveryTransportEvent $DeliveryTransportEvent,
        DeliveryTransportHandler $DeliveryTransportHandler,
    ): Response {
        $DeliveryTransportDTO = new DeliveryTransportDTO();
        $DeliveryTransportEvent->getDto($DeliveryTransportDTO);

        // Форма
        $form = $this->createForm(DeliveryTransportForm::class, $DeliveryTransportDTO);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->has('delivery_transport'))
        {
            $DeliveryTransport = $DeliveryTransportHandler->handle($DeliveryTransportDTO);

            if ($DeliveryTransport instanceof DeliveryTransport)
            {
                $this->addFlash('success', 'admin.success.new', 'admin.delivery.transport');

                return $this->redirectToRoute('delivery-transport:admin.transport.index');
            }

            $this->addFlash('danger', 'admin.danger.new', 'admin.delivery.transport', $DeliveryTransport);

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView()]);
    }
}
