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

namespace BaksDev\DeliveryTransport\Messenger\Package;

use App\Kernel;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Event\DeliveryPackageEvent;
use BaksDev\DeliveryTransport\Repository\Package\ExistPackageProductStocks\ExistPackageProductStocksInterface;
use BaksDev\DeliveryTransport\Repository\Package\ExistStockPackage\ExistStockPackageInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts\PackageOrderProductsInterface;
use BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransportRegion\AllDeliveryTransportRegionInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Error\ErrorProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Error\ErrorProductStockHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\DeliveryPackageHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit\Stocks\DeliveryPackageStocksDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport\DeliveryPackageTransportHandler;
use BaksDev\Orders\Order\Repository\OrderDelivery\OrderDeliveryInterface;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Repository\ExistProductStocksMoveOrder\ExistProductStocksMoveOrderInterface;
use BaksDev\Products\Stocks\Repository\ProductStocksNewByOrder\ProductStocksNewByOrderInterface;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusDivide;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusIncoming;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusMoving;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusPackage;
use BaksDev\Users\Address\Services\GeocodeDistance;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileGps\UserProfileGpsInterface;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 999)]
final class NewPackageByProductStocks
{
    public function __construct(
        #[Target('deliveryTransportLogger')] private readonly LoggerInterface $logger,
        private readonly UserProfileGpsInterface $userProfileGps,
        private readonly EntityManagerInterface $entityManager,
        private readonly ExistProductStocksMoveOrderInterface $existMoveOrder,
        private readonly AllDeliveryTransportRegionInterface $allDeliveryTransportRegion,
        private readonly OrderDeliveryInterface $orderDelivery,
        private readonly GeocodeDistance $geocodeDistance,
        private readonly PackageOrderProductsInterface $packageOrderProducts,
        private readonly DeliveryPackageHandler $deliveryPackageHandler,
        private readonly DeliveryPackageTransportHandler $packageTransportHandler,

        private readonly ExistStockPackageInterface $existStockPackage,
        private readonly ErrorProductStockHandler $errorProductStockHandler,
        private readonly ProductStocksNewByOrderInterface $productStocksNewByOrder,
        private readonly MessageDispatchInterface $messageDispatch,
        private readonly ExistPackageProductStocksInterface $existPackageProductStocks
    ) {}

    /**
     * Добавляем складскую заявку в путевку.
     *
     * В путевой лист добавляется заявка, при статусах
     * Package «Упаковка»
     * Moving «Перемещение»
     * Divide «Заказа разделен на несколько»
     *
     * Если заявка Incoming «Приход на склад» - пробуем закрыть предыдущий путевой лист и добавить заказ в путевой лист, который ожидает это перемещение
     *
     */
    public function __invoke(ProductStockMessage $message): void
    {

        /** TODO: */
        return;

        $isConsumer = $this->messageDispatch->isConsumer('products-stocks');

        /* Получаем статус заявки */
        /** @var ProductStockEvent $ProductStockEvent */
        $ProductStockEvent = $this->entityManager
            ->getRepository(ProductStockEvent::class)
            ->find($message->getEvent());


        if(!$ProductStockEvent)
        {
            return;
        }

        if(!$ProductStockEvent->getOrder())
        {
            $this->logger->notice('Не добавляем в путевой лист складскую заявку: Складская заявка не имеет идентификатора заказа', [self::class.':'.__LINE__]);
            return;
        }

        /**
         * Если Статус не является "Упаковка" - пропускаем погрузку
         */
        if(
            !(
                $ProductStockEvent->getStatus()->equals(new ProductStockStatusPackage())  // заказ отправлен на упаковку
                || $ProductStockEvent->getStatus()->equals(new ProductStockStatusMoving())  // если перемещение
                || $ProductStockEvent->getStatus()->equals(new ProductStockStatusDivide())  // если деление заказа
                //|| $ProductStockEvent->getStatus()->equals(new ProductStockStatusIncoming()) // если приход на склад по заказу
            )
        )
        {
            return;
        }


        $this->logger->info('Добавляем складскую заявку в путевку.', [self::class.':'.__LINE__]);

        /**
         * Если заявка "ПРИНИМАЕМ ПРИХОД НА СКЛАД" - пробуем закрыть путевой лист
         * и добавить заказ в новый путевой лист по заказам, которые ожидали перемещение
         */
        if($ProductStockEvent->getStatus()->equals(ProductStockStatusIncoming::class))
        {
            /*  Если перемещение по заказу - получаем заявку по заказу, и добавляем в путевку  */
            if($ProductStockEvent->getMoveOrder() && $ProductStockEvent->getMoveDestination())
            {
                /** @var  ProductStockEvent $NewProductStocks */
                $NewProductStocks = $this->productStocksNewByOrder
                    ->getProductStocksEventByOrderAndWarehouse($ProductStockEvent->getMoveOrder(), $ProductStockEvent->getMoveDestination());

                if($NewProductStocks)
                {
                    /* Отправляем сообщение в шину */
                    $this->messageDispatch->dispatch(
                        message: new ProductStockMessage($NewProductStocks->getMain(), $NewProductStocks->getId()),
                        transport: 'products-stocks'
                    );
                }
            }
        }


        //dump($ProductStockEvent->getOrder());
        //dd($this->existMoveOrder->existProductMoveOrder($ProductStockEvent->getOrder()));

        /**
         * Если складская заявка на упаковку заказа, но на заказ имеется заявка на перемещение - пропускаем погрузку
         */
        if(
            $ProductStockEvent->getOrder() &&
            $ProductStockEvent->getStatus()->equals(ProductStockStatusMoving::class) === false &&
            $this->existMoveOrder->existProductMoveOrder($ProductStockEvent->getOrder()) === true
        )
        {
            return;
        }

        /**
         * Если заявка уже имеется в поставке - пропускаем погрузку
         */
        if($this->existStockPackage->isExistStockPackage($message->getId()))
        {
            $this->logger->warning('Не добавляем в путевой лист складскую заявку: Заявка уже имеется в поставке', [self::class.':'.__LINE__]);
            return;
        }

        if($ProductStockEvent->getStatus()->equals(ProductStockStatusMoving::class))
        {
            $destinationProfile = $ProductStockEvent->getMove()?->getDestination();

            /* Получаем геоданные склада назначения */
            $OrderGps = $this->userProfileGps->findUserProfileGps($destinationProfile);

            if(!$OrderGps)
            {
                $msg = 'Склад назначения (профиль) при перемещении не имеет геоданных';

                $this->logger->critical($msg, [
                    self::class.':'.__LINE__,
                    'destination' => (string) $destinationProfile
                ]);

                if($isConsumer)
                {
                    throw new DomainException($msg);
                }

                return;
            }
        }

        else
        {
            /* Получаем геоданные пункта назначения складской заявки */
            $OrderGps = $this->orderDelivery->fetchProductStocksGps($message->getEvent());

            if(!$OrderGps)
            {
                $this->logger->warning('Не добавляем в путевой лист складскую заявку: В заявке отсутствуют геоданные пункта назначения. Возможно заявка является закупкой!', [self::class.':'.__LINE__]);
                return;
            }
        }


        /* Получаем геоданные склада упаковки (Профиля) */
        $UserProfileGps = $this->userProfileGps->findUserProfileGps($ProductStockEvent->getProfile());

        if(!$UserProfileGps)
        {
            $msg = 'Профиль склада не имеет геоданных';

            $this->logger->critical($msg, [
                self::class.':'.__LINE__,
                'profile' => (string) $ProductStockEvent->getProfile()
            ]);

            if($isConsumer)
            {
                throw new DomainException($msg);
            }

            return;
        }

        $profileDistance = $this->geocodeDistance
            ->fromLatitude((float) $OrderGps['latitude'])
            ->fromLongitude((float) $OrderGps['longitude'])
            ->toLatitude((float) $UserProfileGps['latitude'])
            ->toLongitude((float) $UserProfileGps['longitude'])
            ->getDistance();

        /* Если склад упаковки является адресом доставки (точность до 100 м) - не добавляем в путевой лист */
        if($profileDistance <= 0.1)
        {
            $this->logger->info('Не добавляем в путевой лист складскую заявку: Заявка является пунктом самовывоза', [self::class.':'.__LINE__]);
            return;
        }


        /* Получаем параметры упаковки на продукцию в заявке */
        $OrderProductPackage = $this->packageOrderProducts->fetchAllPackageStocksProductsAssociative($message->getEvent());

        /** Получаем транспорт, закрепленный за складом */
        $DeliveryTransportRegion = $this->allDeliveryTransportRegion
            ->getDeliveryTransportRegionGps($ProductStockEvent->getProfile());

        if(!$DeliveryTransportRegion)
        {
            $msg = 'Не добавляем в путевой лист складскую заявку: За складом (профилем) не закреплено ни одного транспорта';

            $this->logger->critical($msg, [
                self::class.':'.__LINE__,
                'profile' => (string) $ProductStockEvent->getProfile()
            ]);

            return;
        }

        /***
         * Определяем последовательность транспорта
         */

        $DeliveryTransportProfileCollection = [];

        /* Сортируем весь транспорт по дистанции до пункта назначения  */
        foreach($DeliveryTransportRegion as $transport)
        {
            $geocodeDistance = $this->geocodeDistance
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

            //dump('Пробуем погрузку '.$deliveryDay);
            //dump($date);

            if($deliveryDay > 30)
            {
                /* Обновляем статус заявки на Error */
                $ErrorProductStockDTO = new ErrorProductStockDTO($ProductStockEvent->getId());
                $this->errorProductStockHandler->handle($ErrorProductStockDTO);

                $this->logger->critical(
                    sprintf('Не добавляем в путевой лист складскую заявку: Невозможно добавить заказ %s в поставку либо по размеру, либо по весу', $ProductStockEvent->getOrder()),
                    [self::class.':'.__LINE__]
                );

                break;
            }

            $DeliveryTransportProfile = $DeliveryTransportProfileCollection;
            $deliveryDay++;

            /**
             * Перебираем транспорт и получаем||добавляем поставку
             */
            foreach($DeliveryTransportProfile as $keyTransport => $DeliveryTransportUid)
            {
                //dump('Получаем путевой лист ');
                //dump($date);

                $DeliveryPackageTransportDTO = new DeliveryPackageTransportDTO();

                /**
                 * Получаем имеющуюся поставку на данный транспорт в указанную дату
                 * @var DeliveryPackageTransport $DeliveryPackageTransport
                 */
                $DeliveryPackageTransport = $this->entityManager->getRepository(DeliveryPackageTransport::class)->findOneBy(
                    ['date' => $date->getTimestamp(), 'transport' => $DeliveryTransportUid]
                );

                $DeliveryPackageDTO = new DeliveryPackageDTO();

                /* Создаем новую поставку на указанную дату, если поставки на данный транспорт не найдено */
                if($DeliveryPackageTransport === null)
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
                            $DeliveryPackage = $this->entityManager->getRepository(DeliveryPackage::class)
                                ->find($DeliveryPackage->getId());
                            $this->entityManager->remove($DeliveryPackage);

                            $DeliveryPackageEvent = $this->entityManager->getRepository(DeliveryPackageEvent::class)
                                ->find($DeliveryPackage->getEvent());

                            if($DeliveryPackageEvent)
                            {
                                $this->entityManager->remove($DeliveryPackageEvent);
                            }

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

                /* Ограничения по объему и грузоподъемности */
                $maxCarrying = $DeliveryTransportUid->getCarrying()->getValue() * 100; // грузоподъемность
                $maxSize = $DeliveryTransportUid->getSize() * 1000; // объем см3 переводим в мм3


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

                        $this->logger->debug(
                            sprintf('Добавили объем %s (в поставке %s при допустимом max %s)', $product['size'], $DeliveryPackageTransportDTO->getSize(), $maxSize),
                            [self::class.':'.__LINE__]
                        );

                        $this->logger->debug(
                            sprintf('Добавили вес %s (в поставке %s при допустимом max %s)', $product['weight'], $DeliveryPackageTransportDTO->getCarrying(), $maxCarrying),
                            [self::class.':'.__LINE__]
                        );

                        /* Если заказ превышает объем или грузоподъемность - пропускаем и продуем добавить в другой транспорт */
                        if($DeliveryPackageTransportDTO->getSize() > $maxSize || $DeliveryPackageTransportDTO->getCarrying() > $maxCarrying)
                        {

                            //dump('Заказ больше не входит в поставку транспорта. Пробуем другой транспорт');
                            $this->logger->info('Не добавляем в путевой лист складскую заявку: Заказ не входит в поставку транспорта. Ищем другой транспорт', [self::class.':'.__LINE__]);

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
                        }
                    }

                }


                /** Добавляем заказ в поставку */
                $DeliveryPackageEvent =
                    $this->entityManager
                        ->getRepository(DeliveryPackageEvent::class)
                        ->find($DeliveryPackage->getEvent());

                $DeliveryPackageEvent->getDto($DeliveryPackageDTO);

                $DeliveryPackageStocksDTO = new DeliveryPackageStocksDTO();
                $DeliveryPackageStocksDTO->setStock($ProductStockEvent->getMain());
                $DeliveryPackageDTO->addStock($DeliveryPackageStocksDTO);

                /* Сохраняем заказ в поставке */
                $DeliveryPackage = $this->deliveryPackageHandler->handle($DeliveryPackageDTO);

                if($DeliveryPackage instanceof DeliveryPackage)
                {
                    /* Сохраняем параметры поставки */
                    $DeliveryPackageTransport = $this->packageTransportHandler->handle($DeliveryPackageTransportDTO);

                    if(!$DeliveryPackageTransport instanceof DeliveryPackageTransport)
                    {
                        $DeliveryPackage = $this->entityManager->getRepository(DeliveryPackage::class)->find($DeliveryPackage->getId());
                        $this->entityManager->remove($DeliveryPackage);

                        $DeliveryPackageEvent = $this->entityManager->getRepository(DeliveryPackageEvent::class)->find($DeliveryPackage->getEvent());
                        $this->entityManager->remove($DeliveryPackageEvent);

                        throw new DomainException(sprintf('Не добавляем в путевой лист складскую заявку: Ошибка %s при создании поставки', $DeliveryPackageTransport));
                    }
                }
                else
                {
                    throw new DomainException(sprintf('Не добавляем в путевой лист складскую заявку: Ошибка %s при создании поставки', $DeliveryPackage));
                }

                $this->logger->info(
                    sprintf('Добавили складскую заявку в путевой лист на дату %s', $date->format('d.m.Y')),
                    [
                        self::class.':'.__LINE__,
                        'DeliveryTransportUid' => $DeliveryTransportUid
                    ]
                );

                break 2;

            }
        }

        $this->logger->info('Складская заявка успешно добавлена в путевку.', [self::class.':'.__LINE__]);
    }
}
