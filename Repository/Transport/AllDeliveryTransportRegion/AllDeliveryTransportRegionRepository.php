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

namespace BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransportRegion;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Entity\Transport\Parameter\DeliveryTransportParameter;
use BaksDev\DeliveryTransport\Entity\Transport\Region\DeliveryTransportRegion;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class AllDeliveryTransportRegionRepository implements AllDeliveryTransportRegionInterface
{
    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    /**
     * Метод получает массив идентификаторов транспорта с геоданными региона обслуживания
     */
    public function getDeliveryTransportRegionGps(UserProfileUid $profile): ?array
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $select = sprintf(
            'new %s(transport.id, region.latitude, region.longitude, parameter.size, parameter.carrying)',
            DeliveryTransportUid::class
        );

        $qb->select($select);

        $qb->from(DeliveryTransport::class, 'transport');

        $qb->join(
            DeliveryTransportEvent::class,
            'event',
            'WITH',
            'event.id = transport.event AND event.active = true AND event.profile = :profile'
        )
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb->join(
            DeliveryTransportRegion::class,
            'region',
            'WITH',
            'region.event = event.id'
        );

        $qb->join(
            DeliveryTransportParameter::class,
            'parameter',
            'WITH',
            'parameter.event = event.id'
        );

        /* Кешируем результат ORM */
        return $qb->enableCache('delivery-transport', '1 day')->getResult();
    }
}
