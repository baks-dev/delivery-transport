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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageOrderGeocode;

use BaksDev\Contacts\Region\Entity\Call\ContactsRegionCall;
use BaksDev\Contacts\Region\Entity\Call\Info\ContactsRegionCallInfo;
use BaksDev\Contacts\Region\Entity\ContactsRegion;
use BaksDev\DeliveryTransport\Entity\Package\Move\DeliveryPackageMove;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
use BaksDev\DeliveryTransport\Type\Package\Event\DeliveryPackageEventUid;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Stocks\Entity\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\ProductStock;
use Doctrine\DBAL\Connection;

final class PackageOrderGeocode implements PackageOrderGeocodeInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
    }

    /**
     * Метод получает все заявки и перемещения в поставке с геоданными.
     */
    public function fetchAllPackageOrderGeocodeAssociative(DeliveryPackageEventUid $event): array|bool
    {
        /** Проверяем, существует ли перемещение по заказу */
//        $qbMoveNotExist = $this->connection->createQueryBuilder();
//        $qbMoveNotExist->select('1');
//        $qbMoveNotExist->from(ProductStockMove::TABLE, 'move');
//        $qbMoveNotExist->where('move.ord = order_package.ord');

        $qb = $this->connection->createQueryBuilder();

        $qb->select('delivery.stock AS id'); // идентификатор заявки
        $qb->from(DeliveryPackageStocks::TABLE, 'delivery');

        $qb->where('delivery.event = :event');
        $qb->setParameter('event', $event, DeliveryPackageEventUid::TYPE);


        $qb->join(
            'delivery',
            ProductStock::TABLE,
            'product_stock',
            'product_stock.id = delivery.stock'
        );


        /* $qb->join(
             'package_stock',
             ProductStockEvent::TABLE,
             'product_stock_event',
             'product_stock_event.id = product_stock.event'
         );*/




        /* Если заявка на доставку - Данные по заказу */



        $qb->leftJoin(
            'product_stock',
            ProductStockOrder::TABLE,
            'product_stock_order',
            'product_stock_order.event = product_stock.event'
        );



        $qb->leftJoin(
            'product_stock_order',
            Order::TABLE,
            'orders',
            'orders.id = product_stock_order.ord'
        );

        $qb->leftJoin(
            'orders',
            OrderUser::TABLE,
            'order_user',
            'order_user.event = orders.event'
        );



        $qb->leftJoin(
            'order_user',
            OrderDelivery::TABLE,
            'order_delivery',
            'order_delivery.orders_user = order_user.id'
        );

        /*
         * Если заявка на перемещение - Данные по складу
         */

        $qb->leftJoin(
            'product_stock',
            ProductStockMove::TABLE,
            'product_stock_move',
            'product_stock_move.event = product_stock.event'
        );

        $qb->leftJoin(
            'product_stock_move',
            ContactsRegionCall::TABLE,
            'call',
            'call.const = product_stock_move.destination'
        );

        //$qb->addSelect('order_delivery.latitude');
        //$qb->addSelect('order_delivery.longitude');

        //$qbMove->addSelect('call_info.latitude');
        //$qbMove->addSelect('call_info.longitude');

        $qb->leftJoin(
            'call',
            ContactsRegion::TABLE,
            'region',
            'region.event = call.event'
        );

        $qb->leftJoin(
            'call',
            ContactsRegionCallInfo::TABLE,
            'call_info',
            'call_info.call = call.id'
        );
        

        $qb->addSelect('CASE
					   WHEN product_stock_order.ord IS NOT NULL THEN order_delivery.latitude
					   WHEN product_stock_move.ord IS NOT NULL THEN call_info.latitude
					   ELSE NULL
					END AS latitude');


        $qb->addSelect('CASE
					   WHEN product_stock_order.ord IS NOT NULL THEN order_delivery.longitude
					   WHEN product_stock_move.ord IS NOT NULL THEN call_info.longitude
					   ELSE NULL
					END AS longitude');

        return $qb->executeQuery()->fetchAllAssociative();

        //$qb->andWhere('NOT EXISTS ('.$qbMoveNotExist->getSQL().')');
        //$qb->andWhere('order_package.event = :event');

        /* Если перемещения ЕСТЬ - получаем геоданные склада */
//        $qbMoveExist = $this->connection->createQueryBuilder();
//        $qbMoveExist->select('1');
//        $qbMoveExist->from(ProductStockMove::TABLE, 'move');
//        $qbMoveExist->where('move.ord = order_package_move.ord');
//
//        $qbMove = $this->connection->createQueryBuilder();
//
//        $qbMove->from(DeliveryPackageMove::TABLE, 'order_package_move');
//
//        $qbMove->select('order_package_move.ord AS id');
//
//        $qbMove->join('order_package_move', ProductStockMove::TABLE, 'move', 'move.ord = order_package_move.ord');
//
//        $qbMove->join('move', ContactsRegionCall::TABLE, 'call', 'call.const = move.destination');
//
//        $qbMove->addSelect('call_info.latitude');
//        $qbMove->addSelect('call_info.longitude');
//
//        $qbMove->join(
//            'call',
//            ContactsRegion::TABLE,
//            'region',
//            'region.event = call.event'
//        );
//
//        $qbMove->join(
//            'call',
//            ContactsRegionCallInfo::TABLE,
//            'call_info',
//            'call_info.call = call.id'
//        );
//
//        $qbMove->andWhere('EXISTS ('.$qbMoveExist->getSQL().')');
//        $qbMove->andWhere('order_package_move.event = :event');
//
//        /** Выполняем результат запроса UNION */
//        $qb = $this->connection->prepare($qb->getSQL().' UNION '.$qbMove->getSQL().' ');
//        $qb->bindValue('event', $event, DeliveryPackageEventUid::TYPE);
//
//        return $qb->executeQuery()->fetchAllAssociative();
    }
}
