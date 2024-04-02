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

namespace BaksDev\DeliveryTransport\Repository\Package\AllDeliveryPackage;

use BaksDev\Contacts\Region\Entity\Call\ContactsRegionCall;
use BaksDev\Contacts\Region\Entity\Call\Trans\ContactsRegionCallTrans;
use BaksDev\Contacts\Region\Entity\ContactsRegion;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Core\Services\Switcher\SwitcherInterface;
use BaksDev\Core\Type\Locale\Locale;
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
use BaksDev\DeliveryTransport\Forms\Package\DeliveryPackageFilterInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\Field\OrderDeliveryField;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Move\ProductStockMove;
use BaksDev\Products\Stocks\Entity\Orders\ProductStockOrder;
use BaksDev\Products\Stocks\Entity\ProductStock;
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
            ->from(DeliveryPackage::TABLE, 'package');


        $dbal
            ->addSelect('package_event.status AS package_status')
            ->join(
                'package',
                DeliveryPackageEvent::TABLE,
                'package_event',
                'package_event.id = package.event'
            );


        $dbal->join(
            'package',
            DeliveryPackageStocks::TABLE,
            'package_stocks',
            'package_stocks.event = package_event.id'
        );


        $dbal->join(
            'package_stocks',
            DeliveryPackageEvent::TABLE,
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
                DeliveryPackageTransport::TABLE,
                'package_transport',
                'package_transport.package = package.id '.($date ? ' AND package_transport.date_package = :date' : '')
            );


        /** Складская заявка */

        $dbal
            ->addSelect('package_stocks.sort')
            ->leftJoin(
                'package_stocks',
                ProductStock::TABLE,
                'product_stocks',
                'product_stocks.id = package_stocks.stock'
            );

        $dbal
            ->join(
            'product_stocks',
            ProductStockEvent::TABLE,
            'product_stocks_event',
            'product_stocks_event.id = product_stocks.event'
        );





        $dbal->leftJoin(
            'product_stocks_event',
            ProductStockOrder::TABLE,
            'product_stocks_order',
            'product_stocks_order.event = product_stocks_event.id'
        );


        $dbal->leftJoin(
            'product_stocks_event',
            ProductStockMove::TABLE,
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
                UserProfilePersonal::TABLE,
                'destination_trans',
                'destination_trans.event = destination.event'
            );


        /* Данные заказа */

        //$dbal->addSelect('orders.id');
        $dbal->leftJoin(
            'product_stocks_order',
            Order::TABLE,
            'orders',
            'orders.id = product_stocks_order.ord OR orders.id = product_stocks_move.ord'
        );


        // $dbal->addSelect('orders_event.status AS order_status'); //->addGroupBy('orders_event.status');
        $dbal->leftJoin(
            'orders',
            OrderEvent::TABLE,
            'orders_event',
            'orders_event.id = orders.event'
        );

        //$dbal->addSelect('order_user.profile AS order_profile'); //->addGroupBy('order_user.profile');
        $dbal->leftJoin(
            'orders',
            OrderUser::TABLE,
            'order_user',
            'order_user.event = orders.event'
        );




        //$dbal->addSelect('order_delivery.latitude');
        //$dbal->addSelect('order_delivery.longitude');
        $dbal->leftJoin(
            'order_user',
            OrderDelivery::TABLE,
            'order_delivery',
            'order_delivery.usr = order_user.id'
        );

        $dbal->leftJoin(
            'order_delivery',
            OrderDeliveryField::TABLE,
            'order_delivery_fields',
            'order_delivery_fields.delivery = order_delivery.id'
        );

        $dbal->leftJoin(
            'order_delivery',
            DeliveryField::TABLE,
            'delivery_field',
            'delivery_field.id = order_delivery_fields.field'
        );

        $dbal->leftJoin(
            'delivery_field',
            DeliveryFieldTrans::TABLE,
            'delivery_field_trans',
            'delivery_field_trans.field = delivery_field.id AND delivery_field_trans.local = :local'
        );

        //$dbal->setParameter('local', new Locale($this->translator->getLocale()), Locale::TYPE);


        //$dbal->addSelect('order_move_event.status AS order_move_status'); //->addGroupBy('order_move_event.status');


        /*$dbal->leftJoin(
            'destination',
            ContactsRegionCallTrans::TABLE,
            'destination_trans',
            'destination_trans.call = destination.id AND destination_trans.local = :local'
        );*/

        $dbal->join(
            'package_transport',
            DeliveryTransport::TABLE,
            'delivery_transport',
            'delivery_transport.id = package_transport.transport'
        );

        $dbal->addSelect('delivery_transport_event.number AS transport_number'); //->addGroupBy('delivery_transport_event.number');
        $dbal->join(
            'delivery_transport',
            DeliveryTransportEvent::TABLE,
            'delivery_transport_event',
            'delivery_transport_event.id = delivery_transport.event AND delivery_transport_event.profile = :profile'
        )
            ->setParameter('profile', $profile, UserProfileUid::TYPE);


        $dbal->addSelect('delivery_transport_trans.name AS transport_name'); //->addGroupBy('delivery_transport_trans.name');
        $dbal->join(
            'delivery_transport',
            DeliveryTransportTrans::TABLE,
            'delivery_transport_trans',
            'delivery_transport_trans.event = delivery_transport.event AND delivery_transport_trans.local = :local'
        );


        //        //$dbal->setParameter('local', new Locale($this->translator->getLocale()), Locale::TYPE);
        //
        //        $dbal->join(
        //            'delivery_transport_event',
        //            ContactsRegionCall::TABLE,
        //            'warehouse',
        //            'warehouse.const = delivery_transport_event.warehouse'
        //        );
        //
        //        $dbal->join(
        //            'warehouse',
        //            ContactsRegion::TABLE,
        //            'warehouse_region',
        //            'warehouse_region.event = warehouse.event'
        //        );
        //
        //        $dbal->addSelect('warehouse_trans.name AS warehouse_name'); //->addGroupBy('warehouse_trans.name');
        //
        //        $dbal->join(
        //            'warehouse',
        //            ContactsRegionCallTrans::TABLE,
        //            'warehouse_trans',
        //            'warehouse_trans.call = warehouse.id AND warehouse_trans.local = :local'
        //        );


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
                UserProfilePersonal::TABLE,
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


        //dump($dbal->fetchAllAssociative());

        return $this->paginator->fetchAllAssociative($dbal);
    }
}
