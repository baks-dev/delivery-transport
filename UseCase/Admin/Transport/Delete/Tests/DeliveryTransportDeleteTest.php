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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete\Tests;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\DeliveryTransport\Controller\Admin\Transport\Tests\DeleteControllerTest;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete\DeliveryTransportDeleteDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete\DeliveryTransportDeleteHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Tests\DeliveryTransportEditTest;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group delivery-transport
 * @group delivery-transport-transport
 *
 * @depends BaksDev\DeliveryTransport\Controller\Admin\Transport\Tests\DeleteControllerTest::class
 * @depends BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Tests\DeliveryTransportEditTest::class
 *
 * @see     DeliveryTransportEditTest
 * @see     DeleteControllerTest
 *
 */
#[When(env: 'test')]
final class DeliveryTransportDeleteTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var ORMQueryBuilder $ORMQueryBuilder */
        $ORMQueryBuilder = $container->get(ORMQueryBuilder::class);
        $qb = $ORMQueryBuilder->createQueryBuilder(self::class);
        $DeliveryTransportUid = new DeliveryTransportUid();

        $qb
            ->from(DeliveryTransport::class, 'main')
            ->where('main.id = :main')
            ->setParameter('main', $DeliveryTransportUid, DeliveryTransportUid::TYPE);

        $qb
            ->select('event')
            ->leftJoin(DeliveryTransportEvent::class,
                'event',
                'WITH',
                'event.id = main.event'
            );


        /** @var DeliveryTransportEvent $DeliveryTransportEvent */
        $DeliveryTransportEvent = $qb->getQuery()->getOneOrNullResult();


        $DeliveryTransportDTO = new DeliveryTransportDTO(new UserUid());
        $DeliveryTransportEvent->getDto($DeliveryTransportDTO);

        self::assertEquals('890-64-45', $DeliveryTransportDTO->getNumber());
        self::assertFalse($DeliveryTransportDTO->getActive());


        /** DeliveryTransportParameterDTO */

        $DeliveryTransportParameterDTO = $DeliveryTransportDTO->getParameter();

        self::assertEquals(200, $DeliveryTransportParameterDTO->getWidth());
        self::assertEquals(200, $DeliveryTransportParameterDTO->getHeight());
        self::assertEquals(200, $DeliveryTransportParameterDTO->getLength());

        $Kilogram = new Kilogram(200);
        self::assertEquals($Kilogram, $DeliveryTransportParameterDTO->getCarrying());


        /** DeliveryTransportRegionDTO */

        $DeliveryTransportRegionDTO = $DeliveryTransportDTO->getRegion();

        $GpsLatitude = new GpsLatitude(GpsLatitude::TEST.'9');
        self::assertEquals($GpsLatitude, $DeliveryTransportRegionDTO->getLatitude());

        $GpsLongitude = new GpsLongitude(GpsLongitude::TEST.'9');
        self::assertEquals($GpsLongitude, $DeliveryTransportRegionDTO->getLongitude());

        self::assertEquals('DeliveryTransportRegionAddressEdit', $DeliveryTransportRegionDTO->getAddress());


        /** DeliveryTransportTransDTO */
        $DeliveryTransportTransDTO = $DeliveryTransportDTO->getTranslate()->current();

        self::assertEquals('DeliveryTransportNameEdit', $DeliveryTransportTransDTO->getName());


        /** DELETE */

        $DeliveryTransportDeleteDTO = new DeliveryTransportDeleteDTO();
        $DeliveryTransportEvent->getDto($DeliveryTransportDeleteDTO);

        /** @var DeliveryTransportDeleteHandler $DeliveryTransportDeleteHandler */
        $DeliveryTransportDeleteHandler = $container->get(DeliveryTransportDeleteHandler::class);
        $handle = $DeliveryTransportDeleteHandler->handle($DeliveryTransportDeleteDTO);
        self::assertTrue(($handle instanceof DeliveryTransport), $handle.': Ошибка DeliveryTransport');

    }


    /**
     * @depends testUseCase
     */
    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);


        $DeliveryTransport = $em
            ->getRepository(DeliveryTransport::class)
            ->find(DeliveryTransportUid::TEST);

        if($DeliveryTransport)
        {
            $em->remove($DeliveryTransport);
        }


        $DeliveryTransportCollection = $em
            ->getRepository(DeliveryTransportEvent::class)
            ->findBy(['main' => DeliveryTransportUid::TEST]);

        foreach($DeliveryTransportCollection as $remove)
        {
            $em->remove($remove);
        }


        $em->flush();
        $em->clear();
        //$em->close();

        self::assertNull($DeliveryTransport);

    }

}
