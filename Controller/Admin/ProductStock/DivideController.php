<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\Controller\Admin\ProductStock;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Event\DeliveryPackageEvent;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts\PackageOrderProductsInterface;
use BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransportRegion\AllDeliveryTransportRegionInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportHandler;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderDelivery\OrderDeliveryInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\Edit\EditOrderHandler;
use BaksDev\Orders\Order\UseCase\Public\Basket\Add\OrderProductDTO;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\UseCase\Admin\Divide\DivideProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Divide\DivideProductStockForm;
use BaksDev\Products\Stocks\UseCase\Admin\Divide\DivideProductStockHandler;
use BaksDev\Products\Stocks\UseCase\Admin\Divide\Products\DivideProductStockProductDTO;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileGps\UserProfileGpsInterface;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_PRODUCT_STOCKS_DIVIDE')]
final class DivideController extends AbstractController
{
    /** Разделить заявку по транспорту так, чтобы он эффективно был помещен в транспорт */
    #[Route('/admin/product/stock/divide/{id}', name: 'admin.divide', methods: ['GET', 'POST'])]
    public function divide(
        Request $request,
        #[MapEntity] ProductStockEvent $ProductStockEvent,
        OrderDeliveryInterface $orderDelivery,
        PackageOrderProductsInterface $packageOrderProducts,
        AllDeliveryTransportRegionInterface $allDeliveryTransportRegion,
        GeocodeDistance $geocodeDistanceService,
        EntityManagerInterface $entityManager,
        DeliveryPackageHandler $deliveryPackageHandler,
        DeliveryPackageTransportHandler $packageTransportHandler,
        DivideProductStockHandler $divideProductStockHandler,
        CurrentOrderEventInterface $currentOrderEvent,
        EditOrderHandler $OrderHandler,
        UserProfileGpsInterface $userProfileGps
    ): Response
    {

        $Order = null;

        if($ProductStockEvent->getOrder())
        {
            /**
             * Получаем заказ.
             */
            $Order = $currentOrderEvent
                ->forOrder($ProductStockEvent->getOrder())
                ->find();
        }


        //        if($Order === null)
        //        {
        //            return new Response('Error 404');
        //        }

        /**
         * @var DivideProductStockDTO $DivideProductStockDTO
         */
        $DivideProductStockDTO = new DivideProductStockDTO();
        $ProductStockEvent->getDto($DivideProductStockDTO);


        // Форма заявки
        $form = $this->createForm(DivideProductStockForm::class, $DivideProductStockDTO, [
            'action' => $this->generateUrl('delivery-transport:admin.divide', ['id' => $ProductStockEvent->getId()]),
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('divide'))
        {
            $this->refreshTokenForm($form);

            /* Если заявка на перемещение */
            if($ProductStockEvent->getMove())
            {
                /** Получаем геоданные склада назначения */
                $OrderGps = $userProfileGps->findUserProfileGps($ProductStockEvent->getMove()->getDestination());
            }

            /* Если заявка на заказ */
            elseif($ProductStockEvent->getOrder())
            {
                /** Получаем геоданные пункта назначения складской заявки */
                $OrderGps = $orderDelivery->fetchProductStocksGps($ProductStockEvent->getId());
            }


            if(!$OrderGps)
            {
                /* Невозможно определить геоданные пункта назначения */
                $this->addFlash('page.index', 'Невозможно определить геоданные пункта назначения', 'delivery-transport.package');
                return $this->redirectToReferer();
            }


            /** Получаем транспорт, закрепленный за складом */
            $DeliveryTransportRegion = $allDeliveryTransportRegion
                ->getDeliveryTransportRegionGps($ProductStockEvent->getInvariable()?->getProfile());

            if(!$DeliveryTransportRegion)
            {
                throw new DomainException(
                    sprintf(
                        'За складом ID: %s не закреплено ни одного транспорта',
                        $ProductStockEvent->getInvariable()?->getProfile())
                );
            }

            /*** Определяем постледовательность транспорта */

            $DeliveryTransportProfileCollection = [];

            /* Сортируем весь транспорт по дистанции до пункта назначения  */
            foreach($DeliveryTransportRegion as $transport)
            {
                $geocodeDistance = $geocodeDistanceService
                    ->fromLatitude((float) $OrderGps['latitude'])
                    ->fromLongitude((float) $OrderGps['longitude'])
                    ->toLatitude((float) $transport->getAttr()->getValue())
                    ->toLongitude((float) $transport->getOption()->getValue())
                    ->getDistance();

                $DeliveryTransportProfileCollection[(string) $geocodeDistance] = $transport;
            }

            ksort($DeliveryTransportProfileCollection);

            /** Дата начала поиска поставки - следующий день */
            $date = (new DateTimeImmutable())->setTime(0, 0);
            $deliveryDay = 1;

            while(true)
            {
                /** Каждый цикл добавляем сутки */
                $interval = new DateInterval('P1D');
                $date = $date->add($interval);

                if($deliveryDay > 30)
                {

                    dd('Невозможно добавить заказ в поставку либо по размеру, либо по весу');
                    /* Невозможно добавить заказ в поставку либо по размеру, либо по весу */
                    $this->addFlash('page.index', 'danger.limit', 'delivery-transport.package');
                    return $this->redirectToReferer();
                }


                $DeliveryTransportProfile = $DeliveryTransportProfileCollection;
                $deliveryDay++;


                /**
                 * Перебираем транспорт и получаем||добавляем поставку
                 */
                foreach($DeliveryTransportProfile as $keyTransport => $DeliveryTransportUid)
                {
                    $DeliveryPackageTransportDTO = new DeliveryPackageTransportDTO();

                    /**
                     * Получаем имеющуюся поставку на данный транспорт в указанную дату
                     * @var DeliveryPackageTransport $DeliveryPackageTransport
                     */
                    $DeliveryPackageTransport = $entityManager->getRepository(DeliveryPackageTransport::class)->findOneBy(
                        ['date' => $date->getTimestamp(), 'transport' => $DeliveryTransportUid]
                    );

                    $DeliveryPackageDTO = new DeliveryPackageDTO();

                    /* Создаем новую поставку на указанную дату, если поставки на данный транспорт не найдено */
                    if($DeliveryPackageTransport === null)
                    {
                        $DeliveryPackage = $deliveryPackageHandler->handle($DeliveryPackageDTO);

                        if($DeliveryPackage instanceof DeliveryPackage)
                        {
                            $DeliveryPackageTransportDTO->setPackage($DeliveryPackage);
                            $DeliveryPackageTransportDTO->setTransport($DeliveryTransportUid);
                            $DeliveryPackageTransportDTO->setDate($date->getTimestamp());

                            $DeliveryPackageTransport = $packageTransportHandler->handle($DeliveryPackageTransportDTO);

                            if(!$DeliveryPackageTransport instanceof DeliveryPackageTransport)
                            {
                                $DeliveryPackage = $entityManager->getRepository(DeliveryPackage::class)
                                    ->find($DeliveryPackage->getId());
                                $entityManager->remove($DeliveryPackage);

                                $DeliveryPackageEvent = $entityManager->getRepository(DeliveryPackageEvent::class)
                                    ->find($DeliveryPackage->getEvent());
                                $entityManager->remove($DeliveryPackageEvent);

                                throw new DomainException(sprintf('Ошибка %s при создании поставки', $DeliveryPackageTransport));
                            }
                        }
                        else
                        {
                            throw new DomainException(sprintf('Ошибка %s при создании поставки', $DeliveryPackage));
                        }
                    }
                    else
                    {
                        $DeliveryPackage = $entityManager->getRepository(DeliveryPackage::class)->find($DeliveryPackageTransport->getPackage());
                    }

                    $DeliveryPackageTransport->getDto($DeliveryPackageTransportDTO);

                    /* Ограничения по объему и грузоподъемности */
                    $maxCarrying = $DeliveryTransportUid->getCarrying()->getValue() * 100; // грузоподъемность
                    $maxSize = $DeliveryTransportUid->getSize() * 1000; // объем см3 переводим в мм3


                    // обрываем упаковку поставки если продукции нет
                    if($DivideProductStockDTO->getProduct()->isEmpty())
                    {
                        //dump('Больше нет продукции для упаковки');
                        break 2;
                    }


                    /**
                     * Перебираем всю продукцию в заявке и пробуем добавлять в поставку.
                     *
                     * @var DivideProductStockProductDTO $product
                     */

                    foreach($DivideProductStockDTO->getProduct() as $product)
                    {
                        /* Параметры упаковки товара */
                        $parameter = $packageOrderProducts
                            ->product($product->getProduct())
                            ->offerConst($product->getOffer())
                            ->variationConst($product->getVariation())
                            ->modificationConst($product->getModification())
                            ->find();

                        if(empty($parameter['size']) || empty($parameter['weight']))
                        {
                            //dd('Для добавления товара в поставку необходимо указать параметры упаковки товара');
                            $this->addFlash('page.index', 'danger.size', 'delivery-transport.package');
                            return $this->redirectToReferer();
                        }


                        /* Добавляем по одной пизиции в поставку */
                        $counter = $product->getTotal();

                        for($i = 0; $i <= $counter; $i++)
                        {
                            if($product->getTotal() <= 0)
                            {
                                $DivideProductStockDTO->removeProduct($product);
                                break;
                            }

                            /** Добавляем размер и вес упаковки (Погрузка) */
                            $DeliveryPackageTransportDTO->addSize($parameter['size']);
                            $DeliveryPackageTransportDTO->addCarrying($parameter['weight']);

                            //dump($DeliveryPackageTransportDTO->getSize().' '.$maxSize);
                            //dump($DeliveryPackageTransportDTO->getCarrying().' '.$maxCarrying);

                            /* Если продукт больше не помещается в транспорт - сохраняем новую заявку и создаем следующую и продуем добавить в другой транспорт */
                            if($DeliveryPackageTransportDTO->getSize() > $maxSize || $DeliveryPackageTransportDTO->getCarrying() > $maxCarrying)
                            {
                                //dump('Заказ больше не входит в поставку транспорта. Пробуем другой транспорт');

                                unset($DeliveryTransportProfile[$keyTransport]);

                                /** Если транспорт отсутствует - добавляем на след. день */
                                if(empty($DeliveryTransportProfile))
                                {
                                    continue 4;
                                }

                                /** Пробуем добавлять в другой транспорт склада */
                                // 1 - обрываем процесс погрузки по одной позиции
                                // 2 - обрываем всю продукцию в заказе
                                break 2;
                                //continue 2;

                                //break empty($DeliveryTransportProfile) ? 2 : ;
                            }

                            /* Создаем новую упаковку на указаную дату  */
                            if(!isset($PackageProducts[$deliveryDay][(string) $DeliveryPackage->getId()]))
                            {
                                /** @var DivideProductStockProductDTO $packageProduct */

                                $PackageProducts[$deliveryDay][(string) $DeliveryPackage->getId()] = new ArrayCollection();
                                //$PackageProducts[(string) $DeliveryPackage->getId()]->add($packageProduct);
                            }


                            /** Ищем в массиве такой продукт */
                            $getPackageProduct = ($PackageProducts[$deliveryDay][(string) $DeliveryPackage->getId()])->filter(function(
                                DivideProductStockProductDTO $element
                            ) use ($product) {

                                if($product->getModification())
                                {
                                    return $product->getModification()->equals($element?->getModification());
                                }

                                if($product->getVariation())
                                {
                                    return $product->getVariation()->equals($element?->getVariation());
                                }

                                if($product->getOffer())
                                {
                                    return $product->getOffer()->equals($element?->getOffer());
                                }


                                return $product->getProduct()->equals($element->getProduct());
                            });


                            $packageProduct = $getPackageProduct->current();

                            /* если продукта еще нет - добавляем */
                            if(!$packageProduct)
                            {
                                $packageProduct = clone $product;
                                $packageProduct->setTotal(0);
                                ($PackageProducts[$deliveryDay][(string) $DeliveryPackage->getId()])->add($packageProduct);
                            }


                            $packageProduct->addTotal(1); // добавляем в упаковку
                            $product->subTotal(1); // снимаем из заказа

                        }
                    }


                    //                    dd($DivideProductStockDTO->getProduct());
                    //
                    //                    dd($DeliveryPackageTransportDTO);


                }

            }


            $collectionOrders = new ArrayCollection();
            $collectionPackage = new ArrayCollection();


            foreach($PackageProducts as $packageProduct)
            {
                /** Создаем новые заказы */
                if($ProductStockEvent->getOrder())
                {

                    $newOrder = new EditOrderDTO();

                    if($Order)
                    {
                        $Order->getDto($newOrder);
                    }

                    $newOrder->resetId();
                    $newOrder->setStatus(new OrderStatus(OrderStatusPackage::class));


                    /** @var OrderProductDTO $orderProduct */
                    foreach($newOrder->getProduct() as $orderProduct)
                    {
                        /** Обнуляем все количество в заказе */
                        $orderPrice = $orderProduct->getPrice();
                        $orderPrice->setTotal(0);
                    }


                    /** @var OrderProductDTO $orderProduct */
                    foreach($newOrder->getProduct() as $orderProduct)
                    {
                        /** Обнуляем количество в заказе */
                        $orderPrice = $orderProduct->getPrice();
                        $orderPrice->setTotal(0);


                        /* Перебираем товары в заявке */
                        /** @var DivideProductStockProductDTO $product */
                        foreach($packageProduct as $packageProductCollection)
                        {
                            foreach($packageProductCollection as $product)
                            {
                                $ProductEvent = $entityManager->getRepository(ProductEvent::class)
                                    ->count(['id' => $orderProduct->getProduct(), 'main' => $product->getProduct()]);

                                if(!$ProductEvent)
                                {
                                    continue;
                                }

                                if($product->getOffer())
                                {
                                    $ProductOffer = $entityManager->getRepository(ProductOffer::class)
                                        ->count(['id' => $orderProduct->getOffer(), 'const' => $product->getOffer()]);

                                    if(!$ProductOffer)
                                    {
                                        continue;
                                    }
                                }

                                if($product->getVariation())
                                {
                                    $ProductVariation = $entityManager->getRepository(ProductVariation::class)
                                        ->count(['id' => $orderProduct->getVariation(), 'const' => $product->getVariation()]);

                                    if(!$ProductVariation)
                                    {
                                        continue;
                                    }
                                }

                                if($product->getModification())
                                {
                                    $ProductOffer = $entityManager->getRepository(ProductModification::class)
                                        ->count(['id' => $orderProduct->getModification(), 'const' => $product->getModification()]);

                                    if(!$ProductOffer)
                                    {
                                        continue;
                                    }
                                }

                                /* товар обязательно должен быть в заказе, поэтому только добавляем количество к товару */
                                $orderPrice->setTotal($product->getTotal());
                            }
                        }
                    }

                    // Удаляем продукцию в заказе с нулевым остатком
                    foreach($newOrder->getProduct() as $orderProduct)
                    {
                        $orderPrice = $orderProduct->getPrice();

                        if(empty($orderPrice->getTotal()))
                        {
                            $newOrder->getProduct()->removeElement($orderProduct);
                        }
                    }


                    $OrderResult = $OrderHandler->handle($newOrder);

                    if(!$OrderResult instanceof Order)
                    {
                        /* Удаляем предыдущие заказы в случае ошибки */
                        foreach($collectionOrders as $remove)
                        {
                            $RemoveOrder = $entityManager->getRepository(Order::class)->find($remove->getId());
                            $entityManager->remove($RemoveOrder);
                        }

                        $this->addFlash('page.index', 'danger.divide', 'delivery-transport.package', $OrderResult);
                        return $this->redirectToReferer();
                    }

                    $collectionOrders->add($OrderResult);


                    /**
                     * Создаем новую заявку на заказ
                     */

                    $NewPackageProductStockDTO = new DivideProductStockDTO();
                    $ProductStockEvent->getDto($NewPackageProductStockDTO);

                    $NewPackageProductStockDTO->resetId();
                    $NewPackageProductStockDTO->setProduct(new ArrayCollection());
                    $NewPackageProductStockDTO->setComment('Заявка разделена на несколько поставок');
                    $NewPackageProductStockDTO->setNumber($DivideProductStockDTO->getNumber());


                    if($ProductStockEvent->getOrder())
                    {
                        $NewPackageProductStockDTO->setNumber($OrderResult->getNumber());
                        $NewPackageProductStockDTO->getOrd()->setOrd($OrderResult->getId());
                    }

                    foreach($packageProduct as $packageProductCollection)
                    {
                        foreach($packageProductCollection as $product)
                        {
                            $containsProducts = $NewPackageProductStockDTO->getProduct()
                                ->filter(function(DivideProductStockProductDTO $element) use ($product) {
                                    return
                                        $element->getProduct()->equals($product->getProduct()) &&
                                        $element->getOffer()?->equals($product->getOffer()) &&
                                        $element->getVariation()?->equals($product->getVariation()) &&
                                        $element->getModification()?->equals($product->getModification());
                                });

                            /* Если товар уже имеется в заявке - добавляем к нему количество */
                            if(!$containsProducts->isEmpty())
                            {
                                continue;
                            }

                            /* Добавляем товар в заявку */
                            //$product->setTotal(1);
                            $NewPackageProductStockDTO->addProduct($product);
                        }
                    }


                    $NewPackageProduct = $divideProductStockHandler->handle($NewPackageProductStockDTO);
                    $collectionPackage->add($NewPackageProduct);

                    if(!$NewPackageProduct instanceof ProductStock)
                    {
                        /* Удаляем предыдущие заявки в случае ошибки */
                        foreach($collectionPackage as $remove)
                        {
                            $RemoveProductStock = $entityManager->getRepository(ProductStock::class)->find($remove->getId());
                            $entityManager->remove($RemoveProductStock);
                        }

                        $this->addFlash('page.index', 'danger.divide', 'delivery-transport.package', $NewPackageProduct);
                        return $this->redirectToReferer();
                    }
                }
            }


            /* Удаляем основной заказ */

            if($ProductStockEvent->getOrder())
            {
                $Order = $entityManager->getRepository(Order::class)->find($ProductStockEvent->getOrder());

                if($Order)
                {
                    $entityManager->remove($Order);
                }
            }


            /* Удаляем основную заявку */

            $ProductStock = $entityManager->getRepository(ProductStock::class)->find($ProductStockEvent->getMain());

            if($ProductStock)
            {
                $entityManager->remove($ProductStock);
            }

            $entityManager->flush();

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView(), 'name' => $ProductStockEvent->getInvariable()?->getNumber()]);
    }
}
