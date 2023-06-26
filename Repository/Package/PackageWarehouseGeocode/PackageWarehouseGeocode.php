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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageWarehouseGeocode;

use BaksDev\Contacts\Region\Entity\Call\ContactsRegionCall;
use BaksDev\Contacts\Region\Entity\Call\Info\ContactsRegionCallInfo;
use BaksDev\Contacts\Region\Entity\ContactsRegion;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use Doctrine\DBAL\Connection;

final class PackageWarehouseGeocode implements PackageWarehouseGeocodeInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
    }

    public function fetchPackageWarehouseGeocodeAssociative(DeliveryPackageUid $package): array|bool
    {
        $qb = $this->connection->createQueryBuilder();


        $qb->from(DeliveryPackageTransport::TABLE, 'package_transport');

        $qb->join(
            'package_transport',
            DeliveryTransport::TABLE,
            'transport',
            'transport.id = package_transport.transport'
        );


        //$qb->addSelect('transport_event.warehouse');
        $qb->join(
            'transport',
            DeliveryTransportEvent::TABLE,
            'transport_event',
            'transport_event.id = transport.event'
        );

        $qb->addSelect('warehouse.id');
        $qb->join(
            'transport_event',
            ContactsRegionCall::TABLE,
            'warehouse',
            'warehouse.const = transport_event.warehouse'
        );

        $qb->join(
            'warehouse',
            ContactsRegion::TABLE,
            'warehouse_region',
            'warehouse_region.event = warehouse.event'
        );


        $qb->addSelect('warehouse_info.latitude');
        $qb->addSelect('warehouse_info.longitude');

        $qb->join(
            'warehouse',
            ContactsRegionCallInfo::TABLE,
            'warehouse_info',
            'warehouse_info.call = warehouse.id'
        );


        $qb->where('package_transport.package = :package');
        $qb->setParameter('package', $package, DeliveryPackageUid::TYPE);

        return $qb->fetchAssociative();
    }
}
