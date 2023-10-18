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

namespace BaksDev\DeliveryTransport\Controller\Admin\ProductStock;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Event\DeliveryPackageEvent;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts\PackageOrderProductsInterface;
use BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransportRegion\AllDeliveryTransportRegionInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Divide\DivideProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Divide\DivideProductStockForm;
use BaksDev\DeliveryTransport\UseCase\Admin\Divide\DivideProductStockHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Divide\Products\DivideProductStockProductDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportHandler;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Repository\OrderDelivery\OrderDeliveryInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderDTO;
use BaksDev\Orders\Order\UseCase\Admin\NewEdit\OrderHandler;
use BaksDev\Orders\Order\UseCase\User\Basket\Add\OrderProductDTO;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Users\Address\Services\GeocodeDistance;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

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
        OrderHandler $OrderHandler,
    ): Response
    {
        /**
         * Получаем заказ.
         */
        $Order = $currentOrderEvent->getCurrentOrderEventOrNull($ProductStockEvent->getOrder());

        if($Order === null)
        {
            throw new DomainException(sprintf('Заказ ID: %s не найден', $ProductStockEvent->getOrder()));
        }

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
            /** Получаем геоданные пункта назначения складской заявки */
            $OrderGps = $orderDelivery->fetchProductStocksGps($ProductStockEvent->getId());

            /** Дата начала поиска поставки - следующий день */
            $date = (new DateTimeImmutable())->setTime(0, 0);
            $deliveryDay = 1;

            while(true)
            {
                /** Каждый цикл добавляем сутки */
                $interval = new DateInterval('P1D');
                $date = $date->add($interval);

                $deliveryDay++;

                if($deliveryDay > 30)
                {
                    /* Невозможно добавить заказ в поставку либо по размеру, либо по весу */
                    $this->addFlash('danger', 'admin.danger.limit', 'admin.delivery.package');
                    return $this->redirectToReferer();
                }

                /** Получаем транспорт, закрепленный за складом */
                $DeliveryTransportRegion = $allDeliveryTransportRegion->getDeliveryTransportRegionGps($ProductStockEvent->getWarehouse());

                if(!$DeliveryTransportRegion)
                {
                    throw new DomainException(sprintf('За складом ID: %s не закреплено ни одного транспорта', $ProductStockEvent->getWarehouse()));
                }

                /* Перебираем транспорт и добавляем в поставку */
                while(true)
                {
                    /* Если транспорта больше нет в массиве - обрываем цикл, и пробуем добавить на следующий день поставку  */
                    if(empty($DeliveryTransportRegion))
                    {
                        break;
                    }

                    $distance = null;
                    $DeliveryTransportUid = null;
                    $current = null;

                    /* Поиск транспорта, ближайшего к точке доставки (по региону обслуживания) */
                    foreach($DeliveryTransportRegion as $key => $transport)
                    {
                        $geocodeDistance = $geocodeDistanceService
                            ->fromLatitude((float) $OrderGps['latitude'])
                            ->fromLongitude((float) $OrderGps['longitude'])
                            ->toLatitude((float) $transport->getAttr()->getValue())
                            ->toLongitude((float) $transport->getOption()->getValue())
                            ->getDistance();

                        if($distance === null || $distance > $geocodeDistance)
                        {
                            $distance = $geocodeDistance;
                            $DeliveryTransportUid = $transport;
                            $current = $key;
                        }
                    }

                    /** Получаем имеющуюся поставку на данный транспорт в указанную дату */
                    $DeliveryPackageTransportDTO = new DeliveryPackageTransportDTO();

                    /** @var DeliveryPackageTransport $DeliveryPackageTransport */
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
                                $DeliveryPackage = $entityManager->getRepository(DeliveryPackage::class)->find($DeliveryPackage->getId());
                                $entityManager->remove($DeliveryPackage);

                                $DeliveryPackageEvent = $entityManager->getRepository(DeliveryPackageEvent::class)->find($DeliveryPackage->getEvent());
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

                    /** Ограничения по объему и грузоподъемности */
                    $maxCarrying = $DeliveryTransportUid->getCarrying()->getValue() * 100; // грузоподъемность
                    $maxSize = $DeliveryTransportUid->getSize(); // объем

                    /**
                     * Перебираем всю продукцию в заказе и пробуем добавлять в поставку.
                     *
                     * @var DivideProductStockProductDTO $product
                     */
                    $break = true;

                    if($DivideProductStockDTO->getProduct()->isEmpty())
                    {
                        break 2;
                    }

                    foreach($DivideProductStockDTO->getProduct() as $product)
                    {
                        if(empty($product->getTotal()))
                        {
                            $DivideProductStockDTO->removeProduct($product);
                            continue;
                        }

                        $parameter = $packageOrderProducts->fetchParameterProductAssociative(
                            $product->getProduct(),
                            $product->getOffer(),
                            $product->getVariation(),
                            $product->getModification()
                        );

                        /* Добавляем по одной пизиции в поставку */
                        for($i = 0; $i <= $product->getTotal(); $i++)
                        {
                            if(empty($parameter['size']) || empty($parameter['weight']))
                            {
                                // Для добавления товара в поставку необходимо указать параметры упаковки товара
                                $this->addFlash('danger', 'admin.danger.size', 'admin.delivery.package');
                                return $this->redirectToReferer();
                            }

                            $DeliveryPackageTransportDTO->addSize($parameter['size']);
                            $DeliveryPackageTransportDTO->addCarrying($parameter['weight']);

                            /* Если продукт больше не помещается в транспорт - сохраняем новую заявку и создаем следующую и продуем добавить в другой транспорт */
                            if($DeliveryPackageTransportDTO->getSize() > $maxSize || $DeliveryPackageTransportDTO->getCarrying() > $maxCarrying)
                            {
                                //dump('Заказ не входит в поставку транспорта. Ищем другой транспорт');

                                /* Удаляем из массива транспорт в поиске ближайшего  */
                                unset($DeliveryTransportRegion[$current]);
                                continue;
                            }

                            //dump('Добавили объем '.$parameter['size'].' поставке '.$DeliveryPackageTransportDTO->getSize().' max '.$maxSize);
                            //dump('Добавили вес '.$parameter['weight'].' поставке '.$DeliveryPackageTransportDTO->getCarrying().' max '.$maxCarrying);

                            /* Создаем  */
                            if(!isset($PackageProducts[(string) $DeliveryPackage->getId()]))
                            {
                                $PackageProducts[(string) $DeliveryPackage->getId()] = new ArrayCollection();
                            }

                            /* Добавляем продукт в массив транспортировки */
                            $PackageProducts[(string) $DeliveryPackage->getId()]->add($product);

                            $break = false;
                            $product->subTotal(1);
                            //dump('Поместили продукт');
                        }
                    }

                    if($break)
                    {
                        break;
                    }
                }
            }


            $collectionOrders = new ArrayCollection();
            $collectionPackage = new ArrayCollection();


            /* Сохраняем новые заявки */
            foreach($PackageProducts as $packageProduct)
            {
                /** Создаем новый заказ */
                $newOrder = new OrderDTO();
                $Order->getDto($newOrder);
                $newOrder->resetId();
                $newOrder->setStatus(new OrderStatus(new OrderStatus\OrderStatusPackage()));

                /** @var OrderProductDTO $orderProduct */
                foreach($newOrder->getProduct() as $orderProduct)
                {
                    /** Обнуляем количество в заказе */
                    $orderPrice = $orderProduct->getPrice();
                    $orderPrice->setTotal(0);

                    /* Перебираем товары в заявке */
                    /** @var DivideProductStockProductDTO $product */
                    foreach($packageProduct as $product)
                    {
                        $ProductEvent = $entityManager->getRepository(ProductEvent::class)
                            ->count(['id' => $orderProduct->getProduct(), 'product' => $product->getProduct()]);

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
                        $orderPrice->addTotal(1);
                    }
                }


                /** Удаляем продукцию в заказе с нулевым остатком */
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
                    /* Удаляем предыдущие заказы */
                    foreach($collectionOrders as $remove)
                    {
                        $RemoveOrder = $entityManager->getRepository(Order::class)->find($remove->getId());
                        $entityManager->remove($RemoveOrder);
                    }

                    $this->addFlash('danger', 'admin.danger.divide', 'admin.delivery.package', $OrderResult);
                    return $this->redirectToReferer();
                }

                $collectionOrders->add($OrderResult);


                //dd('!!!!!!!!!!!!!!!!!!!');

                /** Создаем новую заявку на заказ */
                $NewPackageProductStockDTO = new DivideProductStockDTO();
                $ProductStockEvent->getDto($NewPackageProductStockDTO);

                $NewPackageProductStockDTO->resetId();
                $NewPackageProductStockDTO->setProduct(new ArrayCollection());
                $NewPackageProductStockDTO->setProfile($this->getProfileUid());
                $NewPackageProductStockDTO->setComment('Заказ разделен на несколько поставок');

                $NewPackageProductStockDTO->setNumber($OrderResult->getNumber());
                $NewPackageProductStockDTO->getOrd()->setOrd($OrderResult->getId());

                foreach($packageProduct as $product)
                {
                    $containsProducts = $NewPackageProductStockDTO->getProduct()->filter(function(
                        DivideProductStockProductDTO $element
                    ) use ($product) {
                        return
                            $element->getProduct()->equals($product->getProduct()) &&
                            $element->getOffer()?->equals($product->getOffer()) &&
                            $element->getVariation()?->equals($product->getVariation()) &&
                            $element->getModification()?->equals($product->getModification());
                    });

                    /* Если товар уже имеется в заявке - добавляем к нему количество */
                    if(!$containsProducts->isEmpty())
                    {
                        $containsProducts->current()->addTotal(1);
                        continue;
                    }

                    /* Добавляем товар в заявку */
                    $product->setTotal(1);
                    $NewPackageProductStockDTO->addProduct($product);
                }

                $NewPackageProduct = $divideProductStockHandler->handle($NewPackageProductStockDTO);
                $collectionPackage->add($NewPackageProduct);

                if(!$NewPackageProduct instanceof ProductStock)
                {
                    /* Удаляем предыдущие заявки */
                    foreach($collectionPackage as $remove)
                    {
                        $RemoveProductStock = $entityManager->getRepository(ProductStock::class)->find($remove->getId());
                        $entityManager->remove($RemoveProductStock);
                    }

                    $this->addFlash('danger', 'admin.danger.divide', 'admin.delivery.package', $NewPackageProduct);
                    return $this->redirectToReferer();
                }
            }


            /* Удаляем основной заказ */

            $Order = $entityManager->getRepository(Order::class)->find($ProductStockEvent->getOrder());
            $entityManager->remove($Order);

            /* Удаляем основную заявку */

            $ProductStock = $entityManager->getRepository(ProductStock::class)->find($ProductStockEvent->getMain());
            $entityManager->remove($ProductStock);

            $entityManager->flush();

            return $this->redirectToReferer();
        }

        return $this->render(['form' => $form->createView(), 'name' => $ProductStockEvent->getNumber()]);
    }
}
