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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageWarehouseGeocode;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;

final class PackageWarehouseGeocodeRepository implements PackageWarehouseGeocodeInterface
{

    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
    )
    {

        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    public function fetchPackageWarehouseGeocodeAssociative(DeliveryPackageUid $package): array|false
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(DeliveryPackageTransport::class, 'package_transport')
            ->where('package_transport.package = :package')
            ->setParameter('package', $package, DeliveryPackageUid::TYPE);

        $qb->join(
            'package_transport',
            DeliveryTransport::class,
            'transport',
            'transport.id = package_transport.transport'
        );


        //$qb->addSelect('transport_event.warehouse');
        $qb->join(
            'transport',
            DeliveryTransportEvent::class,
            'transport_event',
            'transport_event.id = transport.event'
        );


        $qb
            ->addSelect('profile.id')
            ->join(
                'transport_event',
                UserProfile::class,
                'profile',
                'profile.id = transport_event.profile'
            );


        $qb
            ->addSelect('profile_personal.latitude')
            ->addSelect('profile_personal.longitude')
            ->join(
                'profile',
                UserProfilePersonal::class,
                'profile_personal',
                'profile_personal.event = profile.event'
            );


        return $qb
            ->enableCache('delivery-transport', 86400)
            ->fetchAssociative();
    }
}
