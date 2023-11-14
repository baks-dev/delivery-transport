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

namespace BaksDev\DeliveryTransport\Messenger\Package\ProductStocks;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Event\DeliveryPackageEvent;
use BaksDev\DeliveryTransport\Repository\Package\ExistPackageProductStocks\ExistPackageProductStocksInterface;
use BaksDev\DeliveryTransport\Repository\Package\ExistStockPackage\ExistStockPackageInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageByProductStocks\PackageByProductStocksInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts\PackageOrderProductsInterface;
use BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransportRegion\AllDeliveryTransportRegionInterface;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusDivide;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Error\ErrorProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Error\ErrorProductStockHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\Stocks\DeliveryPackageStocksDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportHandler;
use BaksDev\Orders\Order\Repository\OrderDelivery\OrderDeliveryInterface;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Repository\ExistProductStocksMoveOrder\ExistProductStocksMoveOrderInterface;
use BaksDev\Products\Stocks\Repository\ProductStocksNewByOrder\ProductStocksNewByOrderInterface;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusIncoming;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusMoving;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusPackage;
use BaksDev\Users\Address\Services\GeocodeDistance;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 999)]
final class PackageByProductStocksIncoming
{

    private EntityManagerInterface $entityManager;

    private ExistProductStocksMoveOrderInterface $existMoveOrder;

    private AllDeliveryTransportRegionInterface $allDeliveryTransportRegion;

    private OrderDeliveryInterface $orderDelivery;

    private GeocodeDistance $geocodeDistance;

    private PackageOrderProductsInterface $packageOrderProducts;

    private DeliveryPackageHandler $deliveryPackageHandler;

    private DeliveryPackageTransportHandler $packageTransportHandler;

    private LoggerInterface $logger;

    private ExistStockPackageInterface $existStockPackage;

    private ErrorProductStockHandler $errorProductStockHandler;

    private ExistPackageProductStocksInterface $existPackageProductStocks;

    private PackageByProductStocksInterface $packageByProductStocks;

    private CompletedPackageHandler $completedPackageHandler;

    private ProductStocksNewByOrderInterface $productStocksNewByOrder;

    private MessageDispatchInterface $messageDispatch;

    public function __construct(
        EntityManagerInterface $entityManager,
        ExistProductStocksMoveOrderInterface $existMoveOrder,
        AllDeliveryTransportRegionInterface $allDeliveryTransportRegion,
        OrderDeliveryInterface $orderDelivery,
        GeocodeDistance $geocodeDistance,
        PackageOrderProductsInterface $packageOrderProducts,
        DeliveryPackageHandler $deliveryPackageHandler,
        DeliveryPackageTransportHandler $packageTransportHandler,
        LoggerInterface $messageDispatchLogger,
        ExistStockPackageInterface $existStockPackage,
        ErrorProductStockHandler $errorProductStockHandler,
        ExistPackageProductStocksInterface $existPackageProductStocks,
        PackageByProductStocksInterface $packageByProductStocks,
        CompletedPackageHandler $completedPackageHandler,
        ProductStocksNewByOrderInterface $productStocksNewByOrder,
        MessageDispatchInterface $messageDispatch,
    )
    {
        $this->entityManager = $entityManager;
        $this->existMoveOrder = $existMoveOrder;
        $this->allDeliveryTransportRegion = $allDeliveryTransportRegion;
        $this->orderDelivery = $orderDelivery;
        $this->geocodeDistance = $geocodeDistance;
        $this->packageOrderProducts = $packageOrderProducts;
        $this->deliveryPackageHandler = $deliveryPackageHandler;
        $this->packageTransportHandler = $packageTransportHandler;
        //$this->existOrderPackage = $existOrderPackage;
        $this->logger = $messageDispatchLogger;
        $this->existStockPackage = $existStockPackage;
        $this->errorProductStockHandler = $errorProductStockHandler;
        $this->existPackageProductStocks = $existPackageProductStocks;
        $this->packageByProductStocks = $packageByProductStocks;

        $this->completedPackageHandler = $completedPackageHandler;
        $this->productStocksNewByOrder = $productStocksNewByOrder;
        $this->messageDispatch = $messageDispatch;
    }

    /**
     * Добавляем складскую заявку в путевку.
     */
    public function __invoke(ProductStockMessage $message): void
    {
        /** Получаем заявку */
        //        $ProductStock = $this->entityManager->getRepository(ProductStock::class)
        //            ->find($message->getId());

        /** Получаем статус заявки */
        $ProductStockEvent = $this->entityManager
            ->getRepository(ProductStockEvent::class)
            ->find($message->getEvent());


        if(!$ProductStockEvent->getStatus()->equals(new ProductStockStatusIncoming()))
        {
            return;
        }

        /** Получаем упаковку с данным заказом */
        $DeliveryPackage = $this->packageByProductStocks->getDeliveryPackageByProductStock($ProductStockEvent->getMain());

        if($DeliveryPackage)
        {
            /* Проверяем, имеются ли еще не выполненные заявки в доставке */
            if(!$this->existPackageProductStocks->isExistStocksDeliveryPackage($DeliveryPackage->getId()))
            {
                /** Если все заказы выданы - меняем статус путевого листа на "ВЫПОЛНЕН"   */
                $CompletedPackageDTO = new CompletedPackageDTO($DeliveryPackage->getEvent());
                $this->completedPackageHandler->handle($CompletedPackageDTO);
            }
        }

        /*  Если перемещение по заказу - получаем заявку по заказу, и добавляем в путевку  */

        if($ProductStockEvent->getMoveOrder() && $ProductStockEvent->getMoveDestination())
        {
            /** @var  ProductStockEvent $NewProductStocks */
            $NewProductStocks = $this->productStocksNewByOrder->getProductStocksEventByOrderAndWarehouse($ProductStockEvent->getMoveOrder(), $ProductStockEvent->getMoveDestination());

            if($NewProductStocks)
            {
                /* Отправляем сообщение в шину */
                $this->messageDispatch->dispatch(
                    message: new ProductStockMessage($NewProductStocks->getMain(), $NewProductStocks->getId()),
                    transport: 'products-stocks'
                );
            }
        }










        //        /*
        //         * Если Статус не является "Упаковка" - пропускаем погрузку
        //         */
        //        if (!$ProductStockEvent ||
        //            !(
        //                $ProductStockEvent->getStatus()->equals(new ProductStockStatusPackage()) || // заказ отправлен на упаковку
        //                $ProductStockEvent->getStatus()->equals(new ProductStockStatusMoving()) || // если перемещение
        //                $ProductStockEvent->getStatus()->equals(new ProductStockStatusDivide()) || // если деление заказа
        //                $ProductStockEvent->getStatus()->equals(new ProductStockStatusIncoming()) // если приход на склад
        //            )
        //        ) {
        //            return;
        //        }


        $this->logger->info('MessageHandler', ['handler' => self::class]);


        /*
         * Если складская заявка на доставку заказа, но на заказ имеется заявка на перемещение - пропускаем погрузку
         */
        if($ProductStockEvent->getOrder() && $this->existMoveOrder->existProductMoveOrder($ProductStockEvent->getOrder()) === true)
        {
            return;
        }

        /*
         * Если заявка уже имеется в поставке - пропускаем погрузку
         */
        if($this->existStockPackage->isExistStockPackage($message->getId()))
        {
            $this->logger->warning('Заявка уже имеется в поставке', ['handler' => self::class]);
            return;
        }


        /** Получаем геоданные пункта назначения складской заявки */
        $OrderGps = $this->orderDelivery->fetchProductStocksGps($message->getEvent());

        if(!$OrderGps)
        {
            $this->logger->warning('В заявке отсутствуют геоданные пункта назначения. Возможно заявка является закупкой!', ['handler' => self::class]);
            return;
        }


        /** Получаем параметры упаковки на продукцию в заявке */
        $OrderProductPackage = $this->packageOrderProducts->fetchAllPackageStocksProductsAssociative($message->getEvent());


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
                /** Обновляем статус заявки на Error */
                $ErrorProductStockDTO = new ErrorProductStockDTO($ProductStockEvent->getId());
                $this->errorProductStockHandler->handle($ErrorProductStockDTO);

                $this->logger->critical(sprintf('Невозможно добавить заказ %s в поставку либо по размеру, либо по весу', $ProductStockEvent->getOrder()));

                break;
            }

            /** Получаем транспорт, закрепленный за складом */
            $DeliveryTransportRegion = $this->allDeliveryTransportRegion->getDeliveryTransportRegionGps($ProductStockEvent->getWarehouse());

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
                    $this->logger->info(sprintf('На дату %s невозможно добавить поставку, пробуем на другую дату', $date->format('d.m.Y')));
                    break;
                }

                $distance = null;
                $DeliveryTransportUid = null;
                $current = null;

                /* Поиск транспорта, ближайшего к точке доставки (по региону обслуживания) */
                foreach($DeliveryTransportRegion as $key => $transport)
                {
                    $geocodeDistance = $this->geocodeDistance
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
                $DeliveryPackageTransport = $this->entityManager->getRepository(DeliveryPackageTransport::class)->findOneBy(
                    ['date' => $date->getTimestamp(), 'transport' => $DeliveryTransportUid]
                );

                $DeliveryPackageDTO = new DeliveryPackageDTO();

                /* Создаем новую поставку на указанную дату, если поставки на данный транспорт не найдено */
                if(empty($DeliveryPackageTransport))
                {
                    $DeliveryPackage = $this->deliveryPackageHandler->handle($DeliveryPackageDTO);

                    if($DeliveryPackage instanceof DeliveryPackage)
                    {
                        $DeliveryPackageTransportDTO->setPackage($DeliveryPackage);
                        $DeliveryPackageTransportDTO->setTransport($DeliveryTransportUid);
                        $DeliveryPackageTransportDTO->setDate($date->getTimestamp());

                        $DeliveryPackageTransport = $this->packageTransportHandler->handle($DeliveryPackageTransportDTO);

                        if(!$DeliveryPackageTransport instanceof DeliveryPackageTransport)
                        {
                            $DeliveryPackage = $this->entityManager->getRepository(DeliveryPackage::class)->find($DeliveryPackage->getId());
                            $this->entityManager->remove($DeliveryPackage);

                            $DeliveryPackageEvent = $this->entityManager->getRepository(DeliveryPackageEvent::class)->find($DeliveryPackage->getEvent());
                            $this->entityManager->remove($DeliveryPackageEvent);

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
                    $DeliveryPackage = $this->entityManager->getRepository(DeliveryPackage::class)->find($DeliveryPackageTransport->getPackage());
                }

                $DeliveryPackageTransport->getDto($DeliveryPackageTransportDTO);

                /** Ограничения по объему и грузоподъемности */
                $maxCarrying = $DeliveryTransportUid->getCarrying()->getValue() * 100; // грузоподъемность
                $maxSize = $DeliveryTransportUid->getSize(); // объем

                $package = true; // по умолчанию все заказы вмещаются

                /* Добавляем по очереди товары в заказе в транспорт */
                foreach($OrderProductPackage as $product)
                {
                    if(empty($product['size']) || empty($product['weight']))
                    {
                        throw new DomainException('Для добавления товара в поставку необходимо указать параметры упаковки товара');
                    }

                    for($i = 1; $i <= $product['total']; $i++)
                    {
                        $DeliveryPackageTransportDTO->addSize($product['size']);
                        $DeliveryPackageTransportDTO->addCarrying($product['weight']);

                        //dump('Добавили объем '.$product['size'].' поставке '.$DeliveryPackageTransportDTO->getSize().' max '.$maxSize);
                        //dump('Добавили вес '.$product['weight'].' поставке '.$DeliveryPackageTransportDTO->getCarrying().' max '.$maxCarrying);

                        $this->logger->info(sprintf('Добавили объем %s поставке %s max %s', $product['size'], $DeliveryPackageTransportDTO->getSize(), $maxSize));
                        $this->logger->info(sprintf('Добавили вес %s поставке %s max %s', $product['weight'], $DeliveryPackageTransportDTO->getCarrying(), $maxCarrying));

                        /* Если заказ превышает объем или грузоподъемность - пропускаем и продуем добавить в другой транспорт */
                        if($DeliveryPackageTransportDTO->getSize() > $maxSize || $DeliveryPackageTransportDTO->getCarrying() > $maxCarrying)
                        {
                            $package = false;

                            /* Удаляем из массива транспорт в поиске ближайшего  */
                            unset($DeliveryTransportRegion[$current]);
                            $this->logger->info('Заказ не входит в поставку транспорта. Ищем другой транспорт');

                            break 2;
                        }
                    }
                }

                if($package === true)
                {
                    /** Добавляем заказ в поставку */
                    $DeliveryPackageEvent = $this->entityManager->getRepository(DeliveryPackageEvent::class)->find(
                        $DeliveryPackage->getEvent()
                    );

                    $DeliveryPackageEvent->getDto($DeliveryPackageDTO);

                    $DeliveryPackageStocksDTO = new DeliveryPackageStocksDTO();
                    $DeliveryPackageStocksDTO->setStock($ProductStockEvent->getMain());
                    $DeliveryPackageDTO->addStock($DeliveryPackageStocksDTO);

                    /** Сохраняем заказ в поставке */
                    $DeliveryPackage = $this->deliveryPackageHandler->handle($DeliveryPackageDTO);

                    if($DeliveryPackage instanceof DeliveryPackage)
                    {
                        /** Сохраняем параметры поставки */
                        $DeliveryPackageTransport = $this->packageTransportHandler->handle($DeliveryPackageTransportDTO);

                        if(!$DeliveryPackageTransport instanceof DeliveryPackageTransport)
                        {
                            $DeliveryPackage = $this->entityManager->getRepository(DeliveryPackage::class)->find($DeliveryPackage->getId());
                            $this->entityManager->remove($DeliveryPackage);

                            $DeliveryPackageEvent = $this->entityManager->getRepository(DeliveryPackageEvent::class)->find($DeliveryPackage->getEvent());
                            $this->entityManager->remove($DeliveryPackageEvent);

                            throw new DomainException(sprintf('Ошибка %s при создании поставки', $DeliveryPackageTransport));
                        }
                    }
                    else
                    {
                        throw new DomainException(sprintf('Ошибка %s при создании поставки', $DeliveryPackage));
                    }

                    $this->logger->info(sprintf('Нашли транспорт и добавили %s - %s', $date->format('d.m.Y'), $DeliveryTransportUid));

                    break 2;
                }
            }
        }


        $this->logger->info('MessageHandlerSuccess', ['handler' => self::class]);
    }
}