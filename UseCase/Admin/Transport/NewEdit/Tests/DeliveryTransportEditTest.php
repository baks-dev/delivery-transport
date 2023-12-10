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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Tests;

use BaksDev\Core\Type\Gps\GpsLatitude;
use BaksDev\Core\Type\Gps\GpsLongitude;
use BaksDev\DeliveryTransport\Controller\Admin\Transport\Tests\EditControllerTest;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportHandler;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group delivery-transport
 * @group delivery-transport-transport
 *
 * @depends BaksDev\DeliveryTransport\Controller\Admin\Transport\Tests\EditControllerTest::class
 *
 * @see EditControllerTest
 */
#[When(env: 'test')]
final class DeliveryTransportEditTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        //self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $DeliveryTransportEvent = $em->getRepository(DeliveryTransportEvent::class)->find(DeliveryTransportEventUid::TEST);
        self::assertNotNull($DeliveryTransportEvent);

        $DeliveryTransportDTO = new DeliveryTransportDTO(new UserUid());
        $DeliveryTransportEvent->getDto($DeliveryTransportDTO);


        self::assertEquals('123-45-64', $DeliveryTransportDTO->getNumber());
        $DeliveryTransportDTO->setNumber('890-64-45');

        self::assertTrue( $DeliveryTransportDTO->getActive());
        $DeliveryTransportDTO->setActive(false);


        /** DeliveryTransportParameterDTO */

        $DeliveryTransportParameterDTO = $DeliveryTransportDTO->getParameter();

        self::assertEquals(100, $DeliveryTransportParameterDTO->getWidth());
        $DeliveryTransportParameterDTO->setWidth(200);


        self::assertEquals(100, $DeliveryTransportParameterDTO->getHeight());
        $DeliveryTransportParameterDTO->setHeight(200);

        self::assertEquals(100, $DeliveryTransportParameterDTO->getLength());
        $DeliveryTransportParameterDTO->setLength(200);


        $Kilogram = new Kilogram(100);
        self::assertEquals($Kilogram, $DeliveryTransportParameterDTO->getCarrying());

        $DeliveryTransportParameterDTO->setCarrying(new Kilogram(200));



        /** DeliveryTransportRegionDTO */

        $DeliveryTransportRegionDTO = $DeliveryTransportDTO->getRegion();

        $GpsLatitude = new GpsLatitude(GpsLatitude::TEST);
        self::assertEquals($GpsLatitude, $DeliveryTransportRegionDTO->getLatitude());
        $DeliveryTransportRegionDTO->setLatitude(new GpsLatitude(GpsLatitude::TEST.'9'));



        $GpsLongitude = new GpsLongitude(GpsLongitude::TEST);
        self::assertEquals($GpsLongitude, $DeliveryTransportRegionDTO->getLongitude());
        $DeliveryTransportRegionDTO->setLongitude(new GpsLongitude(GpsLongitude::TEST.'9'));

        self::assertEquals('DeliveryTransportRegionAddress', $DeliveryTransportRegionDTO->getAddress());
        $DeliveryTransportRegionDTO->setAddress('DeliveryTransportRegionAddressEdit');


        /** DeliveryTransportTransDTO */

        $DeliveryTransportTransDTO = $DeliveryTransportDTO->getTranslate()->current();

        self::assertEquals('DeliveryTransportName', $DeliveryTransportTransDTO->getName());
        $DeliveryTransportTransDTO->setName('DeliveryTransportNameEdit');


        /** UPDATE */

        self::bootKernel();

        /** @var DeliveryTransportHandler $DeliveryTransportHandler */
        $DeliveryTransportHandler = self::getContainer()->get(DeliveryTransportHandler::class);
        $handle = $DeliveryTransportHandler->handle($DeliveryTransportDTO);

        self::assertTrue(($handle instanceof  DeliveryTransport), $handle.': Ошибка DeliveryTransport');

    }

}