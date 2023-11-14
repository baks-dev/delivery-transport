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

namespace BaksDev\DeliveryTransport\Repository\Package\ExistPackageProductStocks;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusDelivery;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\ProductStock;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;
use Doctrine\DBAL\Connection;

final class ExistPackageProductStocks implements ExistPackageProductStocksInterface
{
//    private Connection $connection;
//
//    public function __construct(
//        Connection $connection,
//    ) {
//        $this->connection = $connection;
//    }

    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder) {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Метод проверяет, имеются ли в поставке заявки, которые еще не погрузили.
     */
    public function isExistStocksNotDeliveryPackage(DeliveryPackageUid $package): bool
    {
        $qbExist = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        //$qbExist->select('1');

        $qbExist->from(DeliveryPackage::TABLE, 'package');

        $qbExist->join(
            'package',
            DeliveryPackageStocks::TABLE,
            'package_stock',
            'package_stock.event = package.event'
        );

        $qbExist->join(
            'package_stock',
            ProductStock::TABLE,
            'product_stock',
            'product_stock.id = package_stock.stock'
        );

        $qbExist->join(
            'product_stock',
            ProductStockEvent::TABLE,
            'product_stock_event',
            'product_stock_event.id = product_stock.event AND product_stock_event.status != :status'
        );

        $qbExist->where('package.id = :package');

        //$qb = $this->connection->createQueryBuilder();
        //$qb->select(sprintf('EXISTS(%s)', $qbExist->getSQL()));

        $qbExist->setParameter('package', $package, DeliveryPackageUid::TYPE);
        $qbExist->setParameter('status', new ProductStockStatus(new ProductStockStatusDelivery()), ProductStockStatus::TYPE);

        return $qbExist->fetchExist();

        //return $qb->fetchOne();
    }


    /**
     * Метод проверяет, имеются ли в поставке заявки, которые еще в доставке (со статусом Delivery).
     */
    public function isExistStocksDeliveryPackage(DeliveryPackageUid $package): bool
    {
        $qbExist = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        //$qbExist->select('1');

        $qbExist->from(DeliveryPackage::TABLE, 'package');

        $qbExist->join(
            'package',
            DeliveryPackageStocks::TABLE,
            'package_stock',
            'package_stock.event = package.event'
        );

        $qbExist->join(
            'package_stock',
            ProductStock::TABLE,
            'product_stock',
            'product_stock.id = package_stock.stock'
        );

        $qbExist->join(
            'product_stock',
            ProductStockEvent::TABLE,
            'product_stock_event',
            'product_stock_event.id = product_stock.event AND product_stock_event.status = :status'
        );

        $qbExist->where('package.id = :package');

        return $qbExist->fetchExist();

//        $qb = $this->connection->createQueryBuilder();
//        $qb->select(sprintf('EXISTS(%s)', $qbExist->getSQL()));
//
//        $qb->setParameter('package', $package, DeliveryPackageUid::TYPE);
//        $qb->setParameter('status', new ProductStockStatus(new ProductStockStatusDelivery()), ProductStockStatus::TYPE);
//
//        return $qb->fetchOne();
    }
}
