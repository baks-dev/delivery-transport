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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageOrderGeocode;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\DeliveryTransport\Entity\Package\Move\DeliveryPackageMove;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
use BaksDev\DeliveryTransport\Type\Package\Event\DeliveryPackageEventUid;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Stocks\Entity\Stock\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Stock\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;

final class PackageOrderGeocodeRepository implements PackageOrderGeocodeInterface
{


    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder
    )
    {

        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Метод получает все заявки и перемещения в поставке с геоданными.
     */
    public function fetchAllPackageOrderGeocodeAssociative(DeliveryPackageEventUid $event): array|bool
    {
        /** Проверяем, существует ли перемещение по заказу */
        //        $dbalMoveNotExist = $this->connection->createQueryBuilder();
        //        $dbalMoveNotExist->select('1');
        //        $dbalMoveNotExist->from(ProductStockMove::TABLE, 'move');
        //        $dbalMoveNotExist->where('move.ord = order_package.ord');

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->select('delivery.stock AS id'); // идентификатор заявки

        $dbal->from(DeliveryPackageStocks::TABLE, 'delivery');

        $dbal->where('delivery.event = :event');
        $dbal->setParameter('event', $event, DeliveryPackageEventUid::TYPE);


        $dbal->join(
            'delivery',
            ProductStock::TABLE,
            'product_stock',
            'product_stock.id = delivery.stock'
        );


        /* $dbal->join(
             'package_stock',
             ProductStockEvent::TABLE,
             'product_stock_event',
             'product_stock_event.id = product_stock.event'
         );*/

        /* Если заявка на доставку - Данные по заказу */

        $dbal->leftJoin(
            'product_stock',
            ProductStockOrder::TABLE,
            'product_stock_order',
            'product_stock_order.event = product_stock.event'
        );


        $dbal->leftJoin(
            'product_stock_order',
            Order::TABLE,
            'orders',
            'orders.id = product_stock_order.ord'
        );

        $dbal->leftJoin(
            'orders',
            OrderUser::TABLE,
            'order_user',
            'order_user.event = orders.event'
        );


        $dbal->leftJoin(
            'order_user',
            OrderDelivery::TABLE,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );

        /*
         * Если заявка на перемещение - Данные по складу
         */

        $dbal
            //->addSelect('product_stock_move.destination')
            ->leftJoin(
                'product_stock',
                ProductStockMove::TABLE,
                'product_stock_move',
                'product_stock_move.event = product_stock.event'
            );


        //        $dbal->leftJoin(
        //            'product_stock_move',
        //            ContactsRegionCall::TABLE,
        //            'call',
        //            'call.const = product_stock_move.destination'
        //        );
        //
        //        $dbal->leftJoin(
        //            'call',
        //            ContactsRegion::TABLE,
        //            'region',
        //            'region.event = call.event'
        //        );
        //
        //        $dbal->leftJoin(
        //            'call',
        //            ContactsRegionCallInfo::TABLE,
        //            'call_info',
        //            'call_info.call = call.id'
        //        );


        $dbal->leftJoin(
            'product_stock_move',
            UserProfile::TABLE,
            'profile_destination',
            'profile_destination.id = product_stock_move.destination'
        );

        $dbal
            ->addSelect('profile_destination_personal.latitude')
            ->addSelect('profile_destination_personal.longitude')
            ->leftJoin(
                'profile_destination',
                UserProfilePersonal::TABLE,
                'profile_destination_personal',
                'profile_destination_personal.event = profile_destination.event'
            );


        $dbal->addSelect('CASE
					   WHEN product_stock_move.destination IS NOT NULL THEN profile_destination_personal.latitude
					   WHEN product_stock_order.ord IS NOT NULL THEN order_delivery.latitude
					   ELSE NULL
					END AS latitude');


        $dbal->addSelect('CASE
                       WHEN product_stock_move.destination IS NOT NULL THEN profile_destination_personal.longitude
					   WHEN product_stock_order.ord IS NOT NULL THEN order_delivery.longitude
					   ELSE NULL
					END AS longitude');

        $dbal->orderBy('delivery.sort');

        return $dbal->fetchAllAssociative();


        //$dbal->andWhere('NOT EXISTS ('.$dbalMoveNotExist->getSQL().')');
        //$dbal->andWhere('order_package.event = :event');

        /* Если перемещения ЕСТЬ - получаем геоданные склада */
        //        $dbalMoveExist = $this->connection->createQueryBuilder();
        //        $dbalMoveExist->select('1');
        //        $dbalMoveExist->from(ProductStockMove::TABLE, 'move');
        //        $dbalMoveExist->where('move.ord = order_package_move.ord');
        //
        //        $dbalMove = $this->connection->createQueryBuilder();
        //
        //        $dbalMove->from(DeliveryPackageMove::TABLE, 'order_package_move');
        //
        //        $dbalMove->select('order_package_move.ord AS id');
        //
        //        $dbalMove->join('order_package_move', ProductStockMove::TABLE, 'move', 'move.ord = order_package_move.ord');
        //
        //        $dbalMove->join('move', ContactsRegionCall::TABLE, 'call', 'call.const = move.destination');
        //
        //        $dbalMove->addSelect('call_info.latitude');
        //        $dbalMove->addSelect('call_info.longitude');
        //
        //        $dbalMove->join(
        //            'call',
        //            ContactsRegion::TABLE,
        //            'region',
        //            'region.event = call.event'
        //        );
        //
        //        $dbalMove->join(
        //            'call',
        //            ContactsRegionCallInfo::TABLE,
        //            'call_info',
        //            'call_info.call = call.id'
        //        );
        //
        //        $dbalMove->andWhere('EXISTS ('.$dbalMoveExist->getSQL().')');
        //        $dbalMove->andWhere('order_package_move.event = :event');
        //
        //        /** Выполняем результат запроса UNION */
        //        $dbal = $this->connection->prepare($dbal->getSQL().' UNION '.$dbalMove->getSQL().' ');
        //        $dbal->bindValue('event', $event, DeliveryPackageEventUid::TYPE);
        //
        //        return $dbal->executeQuery()->fetchAllAssociative();
    }
}
