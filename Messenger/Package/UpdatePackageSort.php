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

namespace BaksDev\DeliveryTransport\Messenger\Package;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Move\DeliveryPackageMove;
use BaksDev\DeliveryTransport\Entity\Package\Order\DeliveryPackageOrder;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderGeocode\PackageOrderGeocodeInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageWarehouseGeocode\PackageWarehouseGeocodeInterface;
use BaksDev\Users\Address\Services\GeocodeNavigator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 99)]
final class UpdatePackageSort
{
    private EntityManagerInterface $entityManager;

    private PackageOrderGeocodeInterface $packageOrderGeocode;

    private PackageWarehouseGeocodeInterface $packageWarehouseGeocode;

    private GeocodeNavigator $geocodeNavigator;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        PackageOrderGeocodeInterface $packageOrderGeocode,
        PackageWarehouseGeocodeInterface $packageWarehouseGeocode,
        GeocodeNavigator $geocodeNavigator,
        LoggerInterface $messageDispatchLogger
    ) {
        $this->entityManager = $entityManager;
        $this->packageOrderGeocode = $packageOrderGeocode;
        $this->packageWarehouseGeocode = $packageWarehouseGeocode;
        $this->geocodeNavigator = $geocodeNavigator;
        $this->logger = $messageDispatchLogger;
    }

    /**
     * Сортируем поставку согласно общему маршруту (у кого больше маршрут - тот первый на погрузку)
     * Сортируем заказы по расстоянию для постройки маршрута.
     */
    public function __invoke(DeliveryPackageMessage $message): bool
    {
        $this->logger->info('MessageHandler', ['handler' => self::class]);

        /** Определяем геолокацию склада погрузки (начальную точку) */
        $DeliveryPackageUid = $message->getId();
        $geoWarehouse = $this->packageWarehouseGeocode->fetchPackageWarehouseGeocodeAssociative($DeliveryPackageUid);

        if ($geoWarehouse === false)
        {
            // 'Невозможно определить путевой лист'
            return false;
        }

        /*
         * Присваиваем начальную точку навигатору
         */
        $this->geocodeNavigator->withStart(new GpsLatitude($geoWarehouse['latitude']), new  GpsLongitude($geoWarehouse['longitude']));

        /**
         * Получаем все заказы в путевом листе с геолокацией.
         */
        $DeliveryPackageEventUid = $message->getEvent();

        $geoData = $this->packageOrderGeocode->fetchAllPackageOrderGeocodeAssociative($DeliveryPackageEventUid);

        foreach ($geoData as $geo)
        {
            /* Добавляем к навигации точки заказов */
            $this->geocodeNavigator->addGeocode(new GpsLatitude($geo['latitude']), new  GpsLongitude($geo['longitude']), $geo['id']);
        }

        /**
         * Сортированный массив геолокации для маршрута.
         */
        $navigator = $this->geocodeNavigator->getNavigate();

        /*
         * Обновляем сортировку заказов в листе погрузки
         */
        foreach ($navigator as $sort => $order)
        {
            /** Обновляем сортировку, если упаковка заказа */

            $DeliveryPackageStocks = $this->entityManager->getRepository(DeliveryPackageStocks::class)->findOneBy(
                ['event' => $DeliveryPackageEventUid, 'stock' => $order['attr']]
            );

            $DeliveryPackageStocks?->setSort( $sort + 1);

        }

        /**
         * Общее количество километража по навигации.
         */
        $interval = $this->geocodeNavigator->getInterval();

        /**
         * Обновляем сортировку путевого листа.
         */
        $DeliveryPackage = $this->entityManager->getRepository(DeliveryPackageTransport::class)->findOneBy(
            ['package' => $DeliveryPackageUid]
        );
        $DeliveryPackage->setInterval($interval);

        $this->entityManager->flush();
        return true;
    }
}
