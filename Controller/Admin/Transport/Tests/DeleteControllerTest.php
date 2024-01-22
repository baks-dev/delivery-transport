<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\DeliveryTransport\Controller\Admin\Transport\Tests;

use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\Users\User\Tests\TestUserAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Tests\DeliveryTransportNewTest;

/**
 * @group delivery-transport
 * @group delivery-transport-transport
 *
 * @depends BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Tests\DeliveryTransportNewTest::class
 */
#[When(env: 'test')]
final class DeleteControllerTest extends WebTestCase
{
    private const URL = '/admin/delivery/transport/delete/%s';

    private const ROLE = 'ROLE_DELIVERY_TRANSPORT_DELETE';


    /** Доступ по роли */
    public function testRoleSuccessful(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getModer(self::ROLE);

            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, DeliveryTransportEventUid::TEST));

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);

    }

    // доступ по роли ROLE_ADMIN
    public function testRoleAdminSuccessful(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getAdmin();

            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, DeliveryTransportEventUid::TEST));

            self::assertResponseIsSuccessful();
        }

        self::assertTrue(true);
    }

    // доступ по роли ROLE_USER
    public function testRoleUserDeny(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $usr = TestUserAccount::getUsr();
            $client->loginUser($usr, 'user');
            $client->request('GET', sprintf(self::URL, DeliveryTransportEventUid::TEST));

            self::assertResponseStatusCodeSame(403);
        }

        self::assertTrue(true);

    }

    /** Доступ по без роли */
    public function testGuestFiled(): void
    {

        self::ensureKernelShutdown();
        $client = static::createClient();

        foreach(TestUserAccount::getDevice() as $device)
        {
            $client->setServerParameter('HTTP_USER_AGENT', $device);

            $client->request('GET', sprintf(self::URL, DeliveryTransportEventUid::TEST));

            // Full authentication is required to access this resource
            self::assertResponseStatusCodeSame(401);
        }

        self::assertTrue(true);

    }
}
