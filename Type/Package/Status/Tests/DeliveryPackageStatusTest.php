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

namespace BaksDev\DeliveryTransport\Type\Package\Status\Tests;

use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatus;
use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatus\Collection\DeliveryPackageStatusCollection;
use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatusType;
use BaksDev\Wildberries\Orders\Type\WildberriesStatus\Status\Collection\WildberriesStatusInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('delivery-transport')]
#[When(env: 'test')]
final class DeliveryPackageStatusTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var DeliveryPackageStatusCollection $DeliveryPackageStatusCollection */
        $DeliveryPackageStatusCollection = self::getContainer()->get(DeliveryPackageStatusCollection::class);

        /** @var WildberriesStatusInterface $case */
        foreach($DeliveryPackageStatusCollection->cases() as $case)
        {
            $DeliveryPackageStatus = new DeliveryPackageStatus($case->getValue());

            self::assertTrue($DeliveryPackageStatus->equals($case::class)); // немспейс интерфейса
            self::assertTrue($DeliveryPackageStatus->equals($case)); // объект интерфейса
            self::assertTrue($DeliveryPackageStatus->equals($case->getValue())); // срока
            self::assertTrue($DeliveryPackageStatus->equals($DeliveryPackageStatus)); // объект класса


            $DeliveryPackageStatusType = new DeliveryPackageStatusType();
            $platform = $this
                ->getMockBuilder(AbstractPlatform::class)
                ->getMock();

            $convertToDatabase = $DeliveryPackageStatusType->convertToDatabaseValue($DeliveryPackageStatus, $platform);
            self::assertEquals($DeliveryPackageStatus->getPackageStatusValue(), $convertToDatabase);

            $convertToPHP = $DeliveryPackageStatusType->convertToPHPValue($convertToDatabase, $platform);
            self::assertInstanceOf(DeliveryPackageStatus::class, $convertToPHP);
            self::assertEquals($case, $convertToPHP->getPackageStatus());

        }

    }
}