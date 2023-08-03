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

namespace BaksDev\DeliveryTransport\Controller\Admin\Package;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Repository\Package\ExistPackageProductStocks\ExistPackageProductStocksInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageByProductStocks\PackageByProductStocksInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockForm;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryProductStockDTO;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductsByProductStocks\ProductsByProductStocksInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_DELIVERY_PACKAGE_COMPLETED')]
final class CompletedController extends AbstractController
{
    /**
     * Выдать заказ клиенту либо складу.
     */
    #[Route('/admin/delivery/package/completed/{id}', name: 'admin.package.completed', methods: ['GET', 'POST'])]
    public function delivery(
        Request $request,
        #[MapEntity] ProductStock $ProductStock,
        CompletedProductStockHandler $CompletedProductStockHandler,
        PackageByProductStocksInterface $packageByProductStocks,
        ExistPackageProductStocksInterface $existPackageProductStocks,
        CompletedPackageHandler $completedPackageHandler,
        ProductsByProductStocksInterface $productDetail,
    ): Response {
        /**
         * @var DeliveryProductStockDTO $DeliveryProductStockDTO
         */
        $CompletedProductStockDTO = new CompletedProductStockDTO($ProductStock->getEvent(), $this->getProfileUid());

        // Форма
        $form = $this->createForm(CompletedProductStockForm::class, $CompletedProductStockDTO, [
            'action' => $this->generateUrl('DeliveryTransport:admin.package.completed', ['id' => $ProductStock->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->has('completed_package'))
        {
            $ProductStockHandler = $CompletedProductStockHandler->handle($CompletedProductStockDTO);

            if ($ProductStockHandler instanceof ProductStock)
            {
                $this->addFlash('success', 'admin.success.completed', 'admin.delivery.completed');

                /* Чистим кеш модуля */
                $cache = new FilesystemAdapter('DeliveryTransport');
                $cache->clear();

                /** Получаем упаковку с данным заказом */
                $DeliveryPackage = $packageByProductStocks->getDeliveryPackageByProductStock($ProductStock->getId());

                if ($DeliveryPackage)
                {
                    /* Проверяем, имеются ли еще не выполненные заявки в доставке */
                    if (!$existPackageProductStocks->isExistStocksDeliveryPackage($DeliveryPackage?->getId()))
                    {
                        /** Если все заказы выданы - меняем статус путевого листа на "ВЫПОЛНЕН"   */
                        $CompletedPackageDTO = new CompletedPackageDTO($DeliveryPackage->getEvent());
                        $CompletedPackageResult = $completedPackageHandler->handle($CompletedPackageDTO);

                        if (!$CompletedPackageResult instanceof DeliveryPackage)
                        {
                            $this->addFlash('danger', 'admin.danger.delivery', 'admin.delivery.package', $CompletedPackageResult);
                        }
                    }
                }

                return $this->redirectToRoute('DeliveryTransport:admin.package.index', ['package' => (string) $DeliveryPackage?->getId()], 200);
            }

            $this->addFlash('danger', 'admin.danger.completed', 'admin.delivery.package', $ProductStockHandler);

            return $this->redirectToReferer();
        }

        return $this->render(
            [
                'form' => $form->createView(),
                'products' => $productDetail->fetchAllProductsByProductStocksAssociative($ProductStock->getId())
            ]
        );
    }
}
