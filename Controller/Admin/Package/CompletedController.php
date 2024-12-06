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
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductsByProductStocks\ProductsByProductStocksInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

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
    ): Response
    {
        /**
         * @var DeliveryProductStockDTO $DeliveryProductStockDTO
         */
        $CompletedProductStockDTO = new CompletedProductStockDTO(
            $ProductStock->getEvent(),
            $this->getProfileUid()
        );

        // Форма
        $form = $this->createForm(CompletedProductStockForm::class, $CompletedProductStockDTO, [
            'action' => $this->generateUrl(
                'delivery-transport:admin.package.completed',
                ['id' => $ProductStock->getId()]
            )]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('completed_package'))
        {
            $this->refreshTokenForm($form);

            $handle = $CompletedProductStockHandler->handle($CompletedProductStockDTO);

            if($handle instanceof ProductStock)
            {
                $this->addFlash(
                    'page.index',
                    'success.completed',
                    'delivery-transport.package'
                );

                /* Чистим кеш модуля */
                $cache = new FilesystemAdapter('delivery-transport');
                $cache->clear();

                /** Получаем упаковку с данным заказом */
                $DeliveryPackage = $packageByProductStocks->getDeliveryPackageByProductStock($ProductStock->getId());

                if($DeliveryPackage)
                {
                    /* Проверяем, имеются ли еще не выполненные заявки в доставке */
                    if(!$existPackageProductStocks->isExistStocksDeliveryPackage($DeliveryPackage?->getId()))
                    {
                        /** Если все заказы выданы - меняем статус путевого листа на "ВЫПОЛНЕН"   */
                        $CompletedPackageDTO = new CompletedPackageDTO($DeliveryPackage->getEvent());
                        $CompletedPackageResult = $completedPackageHandler->handle($CompletedPackageDTO);

                        if(!$CompletedPackageResult instanceof DeliveryPackage)
                        {
                            $this->addFlash(
                                'page.index',
                                'danger.delivery',
                                'delivery-transport.package',
                                $CompletedPackageResult);
                        }
                    }
                }

                return $this->redirectToRoute('delivery-transport:admin.package.index',
                    ['package' => (string) $DeliveryPackage?->getId()]);
            }

            $this->addFlash(
                'page.index',
                'danger.completed',
                'delivery-transport.package',
                $handle);

            return $this->redirectToReferer();
        }


        //dd($productDetail->fetchAllProductsByProductStocksAssociative($ProductStock->getId()));

        return $this->render(
            [
                'form' => $form->createView(),
                'products' => $productDetail->fetchAllProductsByProductStocksAssociative($ProductStock->getId())
            ]
        );
    }
}
