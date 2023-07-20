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
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryProductStockForm;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryProductStockHandler;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductsByProductStocks\ProductsByProductStocksInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RoleSecurity('ROLE_DELIVERY_PACKAGE_DELIVERY')]
final class DeliveryController extends AbstractController
{
    /** Погрузка заявки в транспорт для доставки */
    #[Route('/admin/delivery/package/delivery/{id}', name: 'admin.package.delivery', methods: ['GET', 'POST'])]
    public function delivery(
        Request $request,
        #[MapEntity] ProductStock $Stock,
        DeliveryProductStockHandler $DeliveryProductStockHandler,
        PackageByProductStocksInterface $packageByProductStocks,
        ExistPackageProductStocksInterface $existPackageProductStocks,
        ProductsByProductStocksInterface $productDetail,
        DeliveryPackageHandler $deliveryPackageHandler,
    ): Response
    {
        /**
         * @var DeliveryProductStockDTO $DeliveryProductStockDTO
         */
        $DeliveryProductStockDTO = new DeliveryProductStockDTO($Stock->getEvent(), $this->getProfileUid());

        // Форма
        $form = $this->createForm(DeliveryProductStockForm::class, $DeliveryProductStockDTO, [
            'action' => $this->generateUrl('DeliveryTransport:admin.package.delivery', ['id' => $Stock->getId()]),
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('delivery_package'))
        {
            $ProductStock = $DeliveryProductStockHandler->handle($DeliveryProductStockDTO);

            if($ProductStock instanceof ProductStock)
            {
                $this->addFlash('success', 'admin.success.delivery', 'admin.delivery.package');

                /* Чистим кеш модуля */
                $cache = new FilesystemAdapter('DeliveryTransport');
                $cache->clear();

                /** Получаем упаковку с данным заказом */
                $DeliveryPackage = $packageByProductStocks->getDeliveryPackageByProductStock($ProductStock->getId());

                if($DeliveryPackage)
                {
                    /* Проверяем, имеются ли еще заказы на погрузку */
                    if(!$existPackageProductStocks->isExistStocksNotDeliveryPackage($DeliveryPackage?->getId()))
                    {
                        /** Если все погружено - меняем статус путевого листа на "ДОСТАВКА"   */
                        $DeliveryPackageDTO = new DeliveryPackageDTO($DeliveryPackage->getEvent());
                        $DeliveryPackageHandlerResult = $deliveryPackageHandler->handle($DeliveryPackageDTO);

                        if(!$DeliveryPackageHandlerResult instanceof DeliveryPackage)
                        {
                            $this->addFlash('danger', 'admin.danger.delivery', 'admin.delivery.package', $DeliveryPackageHandlerResult);
                        }
                    }
                }

                return $this->redirectToRoute('DeliveryTransport:admin.package.index', ['package' => (string)$DeliveryPackage?->getId()], 200);
            }

            $this->addFlash('danger', 'admin.danger.delivery', 'admin.delivery.package', $ProductStock);

            return $this->redirectToReferer();
        }
        
        return $this->render([
                'form' => $form->createView(),
                'products' => $productDetail->fetchAllProductsByProductStocksAssociative($Stock->getId())
            ]
        );
    }
}
