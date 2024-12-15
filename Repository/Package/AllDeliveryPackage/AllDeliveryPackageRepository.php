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

namespace BaksDev\DeliveryTransport\Repository\Package\AllDeliveryPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Core\Services\Switcher\SwitcherInterface;
use BaksDev\Delivery\Entity\Fields\DeliveryField;
use BaksDev\Delivery\Entity\Fields\Trans\DeliveryFieldTrans;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Package\Event\DeliveryPackageEvent;
use BaksDev\DeliveryTransport\Entity\Package\Move\DeliveryPackageMove;
use BaksDev\DeliveryTransport\Entity\Package\Order\DeliveryPackageOrder;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Entity\Transport\Trans\DeliveryTransportTrans;
use BaksDev\DeliveryTransport\Forms\Package\Admin\DeliveryPackageFilterDTO;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\Field\OrderDeliveryField;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Stock\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AllDeliveryPackageRepository implements AllDeliveryPackageInterface
{

    private PaginatorInterface $paginator;

    private SwitcherInterface $switcher;

    private TranslatorInterface $translator;

    private DBALQueryBuilder $DBALQueryBuilder;

    private ?SearchDTO $search = null;

    private ?DeliveryPackageFilterDTO $filter = null;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
        SwitcherInterface $switcher,
        TranslatorInterface $translator,
    )
    {

        $this->paginator = $paginator;
        $this->switcher = $switcher;
        $this->translator = $translator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(DeliveryPackageFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }


    /** Метод возвращает пагинатор DeliveryPackage */
    public function fetchAllDeliveryPackageAssociative(UserProfileUid $profile): PaginatorInterface
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('package.id AS package_id')
            ->addSelect('package.event AS package_event')
            ->from(DeliveryPackage::class, 'package');


        $dbal
            ->addSelect('package_event.status AS package_status')
            ->join(
                'package',
                DeliveryPackageEvent::class,
                'package_event',
                'package_event.id = package.event'
            );


        $dbal->join(
            'package',
            DeliveryPackageStocks::class,
            'package_stocks',
            'package_stocks.event = package_event.id'
        );


        $dbal->join(
            'package_stocks',
            DeliveryPackageEvent::class,
            'package_stocks_event',
            'package_stocks_event.id = package_stocks.event'
        );


        $date = null;

        if($this->filter?->getDate())
        {
            $date = $this->filter->getDate()?->getTimestamp();
            $dbal->setParameter('date', $date);
        }

        /* Путевой лист */
        $dbal
            ->addSelect('package_transport.date_package AS package_date')
            ->addSelect('package_transport.interval AS package_interval')
            ->join(
                'package',
                DeliveryPackageTransport::class,
                'package_transport',
                'package_transport.package = package.id '.($date ? ' AND package_transport.date_package = :date' : '')
            );


        /** Складская заявка */

        $dbal
            ->addSelect('package_stocks.sort')
            ->leftJoin(
                'package_stocks',
                ProductStock::class,
                'product_stocks',
                'product_stocks.id = package_stocks.stock'
            );

        $dbal
            ->join(
                'product_stocks',
                ProductStockEvent::class,
                'product_stocks_event',
                'product_stocks_event.id = product_stocks.event'
            );


        $dbal->leftJoin(
            'product_stocks_event',
            ProductStockOrder::class,
            'product_stocks_order',
            'product_stocks_order.event = product_stocks_event.id'
        );


        $dbal->leftJoin(
            'product_stocks_event',
            ProductStockMove::class,
            'product_stocks_move',
            'product_stocks_move.event = product_stocks_event.id'
        );


        /** Пункт назначения при перемещении */

        $dbal
            //->addSelect('profile.id')
            ->leftJoin(
                'product_stocks_move',
                UserProfile::class,
                'destination',
                'destination.id = product_stocks_move.destination'
            );


        $dbal
            ->addSelect('destination_trans.username AS destination_name')
            ->addSelect('destination_trans.location AS destination_location')
            ->addSelect('destination_trans.latitude AS destination_latitude')
            ->addSelect('destination_trans.longitude AS destination_longitude')
            ->leftJoin(
                'destination',
                UserProfilePersonal::class,
                'destination_trans',
                'destination_trans.event = destination.event'
            );


        /* Данные заказа */

        //$dbal->addSelect('orders.id');
        $dbal->leftJoin(
            'product_stocks_order',
            Order::class,
            'orders',
            'orders.id = product_stocks_order.ord OR orders.id = product_stocks_move.ord'
        );


        // $dbal->addSelect('orders_event.status AS order_status'); //->addGroupBy('orders_event.status');
        $dbal->leftJoin(
            'orders',
            OrderEvent::class,
            'orders_event',
            'orders_event.id = orders.event'
        );

        //$dbal->addSelect('order_user.profile AS order_profile'); //->addGroupBy('order_user.profile');
        $dbal->leftJoin(
            'orders',
            OrderUser::class,
            'order_user',
            'order_user.event = orders.event'
        );


        //$dbal->addSelect('order_delivery.latitude');
        //$dbal->addSelect('order_delivery.longitude');
        $dbal->leftJoin(
            'order_user',
            OrderDelivery::class,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );

        $dbal->leftJoin(
            'order_delivery',
            OrderDeliveryField::class,
            'order_delivery_fields',
            'order_delivery_fields.delivery = order_delivery.id'
        );

        $dbal->leftJoin(
            'order_delivery',
            DeliveryField::class,
            'delivery_field',
            'delivery_field.id = order_delivery_fields.field'
        );

        $dbal->leftJoin(
            'delivery_field',
            DeliveryFieldTrans::class,
            'delivery_field_trans',
            'delivery_field_trans.field = delivery_field.id AND delivery_field_trans.local = :local'
        );


        $dbal->join(
            'package_transport',
            DeliveryTransport::class,
            'delivery_transport',
            'delivery_transport.id = package_transport.transport'
        );

        $dbal->addSelect('delivery_transport_event.number AS transport_number'); //->addGroupBy('delivery_transport_event.number');
        $dbal->join(
            'delivery_transport',
            DeliveryTransportEvent::class,
            'delivery_transport_event',
            'delivery_transport_event.id = delivery_transport.event AND delivery_transport_event.profile = :profile'
        )
            ->setParameter('profile', $profile, UserProfileUid::TYPE);


        $dbal->addSelect('delivery_transport_trans.name AS transport_name'); //->addGroupBy('delivery_transport_trans.name');
        $dbal->join(
            'delivery_transport',
            DeliveryTransportTrans::class,
            'delivery_transport_trans',
            'delivery_transport_trans.event = delivery_transport.event AND delivery_transport_trans.local = :local'
        );


        $dbal
            //->addSelect('profile.id')
            ->join(
                'delivery_transport_event',
                UserProfile::class,
                'warehouse',
                'warehouse.id = delivery_transport_event.profile'
            );


        $dbal
            ->addSelect('warehouse_trans.username AS warehouse_name')
            ->addSelect('warehouse_trans.location AS warehouse_location')
            ->join(
                'warehouse',
                UserProfilePersonal::class,
                'warehouse_trans',
                'warehouse_trans.event = warehouse.event'
            );


        $dbal->addSelect(
            "JSON_AGG
            ( DISTINCT
                
                    JSONB_BUILD_OBJECT
                    (
                    
                        '0', package_stocks.sort,
                        'package_sort', package_stocks.sort,
                       
                       /* Информация о доставке  */
                       
                        'package_order_fields', JSONB_BUILD_OBJECT
                        ( 
           
                            'order_field_name', delivery_field_trans.name,
                            'order_field_type', delivery_field.type,
                            'order_field_value', order_delivery_fields.value
                        ), 
                        
                        
                        
                        'stock_id', package_stocks.stock,
                        
                        'order_id', orders.id,
                        'order_number', orders.number,
                        'order_client', order_user.profile,
                        'order_status', orders_event.status,
                        
                        
                         
                        'destination', destination.event,
                        'stocks_status', product_stocks_event.status,
                        'stocks_comment', product_stocks_event.comment
                    )
            )
			AS package_orders"
        );

        $dbal->addOrderBy('package_transport.date_package');
        $dbal->addOrderBy('package_transport.interval', 'DESC');
        $dbal->addOrderBy('package_stocks.sort', 'DESC');


        $dbal->allGroupByExclude();

        return $this->paginator->fetchAllAssociative($dbal);
    }
}
