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

namespace BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransportRegion;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\DeliveryTransport\Entity\Transport as DeliveryTransportEntity;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;

final class AllDeliveryTransportRegion implements AllDeliveryTransportRegionInterface
{


    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(ORMQueryBuilder $ORMQueryBuilder,)
    {

        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }

    /**
     * Метод получает массив идентификаторов транспорта с геоданными региона обслуживания
     */
    public function getDeliveryTransportRegionGps(ContactsRegionCallConst $warehouse): ?array
    {
        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $select = sprintf('new %s(transport.id, region.latitude, region.longitude, parameter.size, parameter.carrying)', DeliveryTransportUid::class);

        $qb->select($select);

        $qb->from(DeliveryTransportEntity\DeliveryTransport::class, 'transport');

        $qb->join(
            DeliveryTransportEntity\Event\DeliveryTransportEvent::class,
            'event',
            'WITH',
            'event.id = transport.event AND event.active = true AND event.warehouse = :warehouse'
        );

        $qb->join(
            DeliveryTransportEntity\Region\DeliveryTransportRegion::class,
            'region',
            'WITH',
            'region.event = event.id'
        );

        $qb->join(
            DeliveryTransportEntity\Parameter\DeliveryTransportParameter::class,
            'parameter',
            'WITH',
            'parameter.event = event.id'
        );

        $qb->setParameter('warehouse', $warehouse, ContactsRegionCallConst::TYPE);


        /* Кешируем результат ORM */
        return $qb->enableCache('DeliveryTransport', 86400)->getResult();

    }
}
