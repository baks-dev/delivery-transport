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
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RoleSecurity('ROLE_DELIVERY_TRANSPORT_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/delivery/transport/delete/{id}', name: 'admin.transport.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] DeliveryAutoEvent $DeliveryAutoEvent,
        DeliveryAutoDeleteHandler $DeliveryAutoDeleteHandler,
    ): Response {
        
        $DeliveryAutoDeleteDTO = new DeliveryAutoDeleteDTO();
        $DeliveryAutoEvent->getDto($DeliveryAutoDeleteDTO);
        $form = $this->createForm(DeliveryAutoDeleteForm::class, $DeliveryAutoDeleteDTO, [
            'action' => $this->generateUrl('DeliveryTransport:admin.transport.delete', ['id' => $DeliveryAutoDeleteDTO->getEvent()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->has('delivery_transport_delete'))
        {
            $DeliveryAuto = $DeliveryAutoDeleteHandler->handle($DeliveryAutoDeleteDTO);

            if ($DeliveryAuto instanceof DeliveryAuto)
            {
                $this->addFlash('admin.form.header.delete', 'admin.success.delete', 'admin.delivery.transport');

                return $this->redirectToRoute('DeliveryTransport:admin.transport.index');
            }

            $this->addFlash(
                'admin.form.header.delete',
                'admin.danger.delete',
                'admin.contacts.region',
                $DeliveryAuto
            );

            return $this->redirectToRoute('DeliveryTransport:admin.transport.index', status: 400);
        }

        return $this->render([
            'form' => $form->createView(),
            'name' => $DeliveryAutoEvent->getNameByLocale($this->getLocale()), // название согласно локали
        ]);
    }
}
