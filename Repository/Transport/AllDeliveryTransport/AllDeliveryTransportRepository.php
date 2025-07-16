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

namespace BaksDev\DeliveryTransport\Repository\Transport\AllDeliveryTransport;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Entity\Transport\Region\DeliveryTransportRegion;
use BaksDev\DeliveryTransport\Entity\Transport\Trans\DeliveryTransportTrans;
use BaksDev\Users\Profile\UserProfile\Entity\Event\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class AllDeliveryTransportRepository implements AllDeliveryTransportInterface
{

    private PaginatorInterface $paginator;

    private DBALQueryBuilder $DBALQueryBuilder;

    private ?SearchDTO $search = null;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator
    )
    {

        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }


    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    /** Метод возвращает пагинатор DeliveryTransport */
    public function fetchAllDeliveryTransportAssociative(UserProfileUid $profile): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();


        $qb
            ->addSelect('auto.id')
            ->addSelect('auto.event')
            ->from(DeliveryTransport::class, 'auto');

        $qb
            ->addSelect('event.number AS auto_number')
            ->addSelect('event.active AS auto_active')
            ->join(
                'auto',
                DeliveryTransportEvent::class,
                'event',
                'event.id = auto.event AND event.profile = :profile'
            )
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb
            ->addSelect('trans.name AS auto_name')
            ->leftJoin(
                'event',
                DeliveryTransportTrans::class,
                'trans',
                'trans.event = event.id AND trans.local = :local'
            );

        $qb
            ->addSelect('region.address AS auto_address')
            ->leftJoin(
                'event',
                DeliveryTransportRegion::class,
                'region',
                'region.event = event.id'
            );


        $qb
            ->leftJoin(
                'event',
                UserProfile::class,
                'users_profile',
                'users_profile.id = event.profile'
            );

        // Personal
        $qb->addSelect('users_profile_personal.username AS users_profile_username');
        $qb->addSelect('users_profile_personal.location AS users_profile_location');

        $qb->leftJoin(
            'users_profile',
            UserProfilePersonal::class,
            'users_profile_personal',
            'users_profile_personal.event = users_profile.event'
        );


        // Поиск
        if($this->search->getQuery())
        {
            $qb
                ->createSearchQueryBuilder($this->search)
                //->addSearchEqualUid('warehouse.id')
                //->addSearchEqualUid('warehouse.event')
                //->addSearchLike('warehouse_trans.name')
                ->addSearchLike('users_profile_personal.username')
                ->addSearchLike('users_profile_personal.location')
                ->addSearchLike('event.number')
                ->addSearchLike('trans.name');
        }


        return $this->paginator->fetchAllAssociative($qb);

    }
}
