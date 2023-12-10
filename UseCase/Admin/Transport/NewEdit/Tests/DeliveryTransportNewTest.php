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
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Core\Type\Locale\Locales\Ru;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\DeliveryTransportHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Trans\DeliveryTransportTransDTO;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group delivery-transport
 * @group delivery-transport-transport
 */
#[When(env: 'test')]
final class DeliveryTransportNewTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $DeliveryTransport = $em
            ->getRepository(DeliveryTransport::class)
            ->findBy(['id' => DeliveryTransportUid::TEST]);

        foreach($DeliveryTransport as $remove)
        {
            $em->remove($remove);
        }

        $DeliveryTransportEvent = $em
            ->getRepository(DeliveryTransportEvent::class)
            ->findBy(['main' => DeliveryTransportUid::TEST]);

        foreach($DeliveryTransportEvent as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
    }


    public function testUseCase(): void
    {

        $DeliveryTransportDTO = new DeliveryTransportDTO(new UserUid());

        $DeliveryTransportDTO->setNumber('123-45-64');
        self::assertEquals('123-45-64', $DeliveryTransportDTO->getNumber());

        $DeliveryTransportDTO->setActive(true);
        self::assertTrue( $DeliveryTransportDTO->getActive());


        $UserProfileUid = new UserProfileUid('0189edbf-fa77-7ad2-9197-1b10743c62f2');
        $DeliveryTransportDTO->setProfile($UserProfileUid);
        self::assertSame($UserProfileUid, $DeliveryTransportDTO->getProfile());


        /** DeliveryTransportParameterDTO */

        $DeliveryTransportParameterDTO = $DeliveryTransportDTO->getParameter();

        $DeliveryTransportParameterDTO->setWidth(100);
        self::assertEquals(100, $DeliveryTransportParameterDTO->getWidth());


        $DeliveryTransportParameterDTO->setHeight(100);
        self::assertEquals(100, $DeliveryTransportParameterDTO->getHeight());

        $DeliveryTransportParameterDTO->setLength(100);
        self::assertEquals(100, $DeliveryTransportParameterDTO->getLength());

        $Kilogram = new Kilogram(100);
        $DeliveryTransportParameterDTO->setCarrying($Kilogram);
        self::assertEquals($Kilogram, $DeliveryTransportParameterDTO->getCarrying());


        /** DeliveryTransportRegionDTO */

        $DeliveryTransportRegionDTO = $DeliveryTransportDTO->getRegion();

        $GpsLatitude = new GpsLatitude(GpsLatitude::TEST);
        $DeliveryTransportRegionDTO->setLatitude($GpsLatitude);
        self::assertEquals($GpsLatitude, $DeliveryTransportRegionDTO->getLatitude());

        $GpsLongitude = new GpsLongitude(GpsLongitude::TEST);
        $DeliveryTransportRegionDTO->setLongitude($GpsLongitude);
        self::assertEquals($GpsLongitude, $DeliveryTransportRegionDTO->getLongitude());

        $DeliveryTransportRegionDTO->setAddress('DeliveryTransportRegionAddress');
        self::assertEquals('DeliveryTransportRegionAddress', $DeliveryTransportRegionDTO->getAddress());


        /** DeliveryTransportTransDTO */

        $DeliveryTransportTransDTO = new DeliveryTransportTransDTO();

        $Locale = new Locale('ru');
        $DeliveryTransportTransDTO->setLocal($Locale);
        self::assertEquals($Locale, $DeliveryTransportTransDTO->getLocal());

        $DeliveryTransportTransDTO->setName('DeliveryTransportName');
        self::assertEquals('DeliveryTransportName', $DeliveryTransportTransDTO->getName());

        $DeliveryTransportDTO->addTranslate($DeliveryTransportTransDTO);
        self::assertTrue($DeliveryTransportDTO->getTranslate()->contains($DeliveryTransportTransDTO));

        /** PERSIST */

        self::bootKernel();

        /** @var DeliveryTransportHandler $DeliveryTransportHandler */
        $DeliveryTransportHandler = self::getContainer()->get(DeliveryTransportHandler::class);
        $handle = $DeliveryTransportHandler->handle($DeliveryTransportDTO);

        self::assertTrue(($handle instanceof  DeliveryTransport), $handle.': Ошибка DeliveryTransport');

    }


    public function testComplete(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $DeliveryTransport = $em->getRepository(DeliveryTransport::class)->find(DeliveryTransportUid::TEST);
        self::assertNotNull($DeliveryTransport);
    }
}