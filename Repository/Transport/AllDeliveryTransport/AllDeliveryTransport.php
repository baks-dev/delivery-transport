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

namespace BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransport;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Core\Services\Switcher\SwitcherInterface;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\DeliveryTransport\Entity\Transport as DeliveryTransportEntity;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AllDeliveryTransport implements AllDeliveryTransportInterface
{

    private PaginatorInterface $paginator;

    private SwitcherInterface $switcher;

    private TranslatorInterface $translator;
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
     DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
        SwitcherInterface $switcher,
        TranslatorInterface $translator,
    ) {

        $this->paginator = $paginator;
        $this->switcher = $switcher;
        $this->translator = $translator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /** Метод возвращает пагинатор DeliveryTransport */
    public function fetchAllDeliveryTransportAssociative(SearchDTO $search): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);


        $qb->addSelect('auto.id');
        $qb->addSelect('auto.event');
        $qb->from(DeliveryTransportEntity\DeliveryTransport::TABLE, 'auto');

        $qb->addSelect('event.number AS auto_number');
        $qb->addSelect('event.active AS auto_active');
        $qb->join(
            'auto',
            DeliveryTransportEntity\Event\DeliveryTransportEvent::TABLE,
            'event',
            'event.id = auto.event'
        );

        $qb->addSelect('trans.name AS auto_name');
        $qb->leftJoin(
            'event',
            DeliveryTransportEntity\Trans\DeliveryTransportTrans::TABLE,
            'trans',
            'trans.event = event.id AND trans.local = :local'
        );

        $qb->addSelect('region.address AS auto_address');
        $qb->leftJoin(
            'event',
            DeliveryTransportEntity\Region\DeliveryTransportRegion::TABLE,
            'region',
            'region.event = event.id'
        );


        $qb->setParameter('local', new Locale($this->translator->getLocale()), Locale::TYPE);



        return $this->paginator->fetchAllAssociative($qb);

    }
}
