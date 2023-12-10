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
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete\DeliveryTransportDeleteDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete\DeliveryTransportDeleteForm;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete\DeliveryTransportDeleteHandler;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_DELIVERY_TRANSPORT_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/delivery/transport/delete/{id}', name: 'admin.transport.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] DeliveryTransportEvent $DeliveryTransportEvent,
        DeliveryTransportDeleteHandler $DeliveryTransportDeleteHandler,
    ): Response
    {

        $DeliveryTransportDeleteDTO = new DeliveryTransportDeleteDTO();
        $DeliveryTransportEvent->getDto($DeliveryTransportDeleteDTO);
        $form = $this->createForm(DeliveryTransportDeleteForm::class, $DeliveryTransportDeleteDTO, [
            'action' => $this->generateUrl('delivery-transport:admin.transport.delete', ['id' => $DeliveryTransportDeleteDTO->getEvent()]),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('delivery_transport_delete'))
        {
            $DeliveryTransport = $DeliveryTransportDeleteHandler->handle($DeliveryTransportDeleteDTO);

            if($DeliveryTransport instanceof DeliveryTransport)
            {
                $this->addFlash('admin.page.delete', 'admin.success.delete', 'admin.delivery.transport');

                return $this->redirectToRoute('delivery-transport:admin.transport.index');
            }

            $this->addFlash(
                'admin.page.delete',
                'admin.danger.delete',
                'admin.delivery.transport',
                $DeliveryTransport
            );

            return $this->redirectToRoute('delivery-transport:admin.transport.index', status: 400);
        }

        if(!$this->isGranted('ROLE_ADMIN') && !$DeliveryTransportDeleteDTO->getProfile()?->equals($this->getProfileUid()))
        {
            throw new InvalidArgumentException('Page Not Found');
        }

        return $this->render([
            'form' => $form->createView(),
            'name' => $DeliveryTransportEvent->getNameByLocale($this->getLocale()), // название согласно локали
        ]);
    }
}
