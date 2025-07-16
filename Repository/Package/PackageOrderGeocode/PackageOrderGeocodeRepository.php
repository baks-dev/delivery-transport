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
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
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

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->select('delivery.stock AS id'); // идентификатор заявки

        $dbal
            ->from(DeliveryPackageStocks::class, 'delivery')
            ->where('delivery.event = :event')
            ->setParameter('event', $event, DeliveryPackageEventUid::TYPE);


        $dbal->join(
            'delivery',
            ProductStock::class,
            'product_stock',
            'product_stock.id = delivery.stock'
        );


        /* Если заявка на доставку - Данные по заказу */

        $dbal->leftJoin(
            'product_stock',
            ProductStockOrder::class,
            'product_stock_order',
            'product_stock_order.event = product_stock.event'
        );


        $dbal->leftJoin(
            'product_stock_order',
            Order::class,
            'orders',
            'orders.id = product_stock_order.ord'
        );

        $dbal->leftJoin(
            'orders',
            OrderUser::class,
            'order_user',
            'order_user.event = orders.event'
        );


        $dbal->leftJoin(
            'order_user',
            OrderDelivery::class,
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
                ProductStockMove::class,
                'product_stock_move',
                'product_stock_move.event = product_stock.event'
            );



        $dbal->leftJoin(
            'product_stock_move',
            UserProfile::class,
            'profile_destination',
            'profile_destination.id = product_stock_move.destination'
        );

        $dbal
            ->addSelect('profile_destination_personal.latitude')
            ->addSelect('profile_destination_personal.longitude')
            ->leftJoin(
                'profile_destination',
                UserProfilePersonal::class,
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

    }
}
