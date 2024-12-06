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

namespace BaksDev\DeliveryTransport\Messenger\Package\Orders;

use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusCompleted;
use BaksDev\Products\Stocks\UseCase\Admin\Warehouse\WarehouseProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Warehouse\WarehouseProductStockHandler;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class NewProductStocksWarehouseByMove
{
    private EntityManagerInterface $entityManager;

    private WarehouseProductStockHandler $WarehouseProductStockHandler;

    private UserByUserProfileInterface $userByUserProfile;

    private LoggerInterface $logger;


    public function __construct(
        UserByUserProfileInterface $userByUserProfile,
        EntityManagerInterface $entityManager,
        LoggerInterface $deliveryTransportLogger,
        WarehouseProductStockHandler $WarehouseProductStockHandler,
    )
    {

        $this->entityManager = $entityManager;
        $this->WarehouseProductStockHandler = $WarehouseProductStockHandler;
        $this->userByUserProfile = $userByUserProfile;
        $this->logger = $deliveryTransportLogger;
    }

    /**
     * Создаем приход на склад при перемещении продукции между складами по заказу когда заявка Completed «Выдан по месту назначения»
     */
    public function __invoke(ProductStockMessage $message): void
    {

        /** TODO: */
        return;

        $ProductStockEvent = $this->entityManager->getRepository(ProductStockEvent::class)
            ->find($message->getEvent());

        if(!$ProductStockEvent)
        {
            return;
        }

        // Если Статус складской заявки не является Completed «Выдан по месту назначения»
        if($ProductStockEvent->getStatus()->equals(ProductStockStatusCompleted::class) === false)
        {
            $this->logger
                ->notice('Не создаем приход: Статус складской заявки не является Completed «Выдан по месту назначения»',
                    [self::class.':'.__LINE__]);

            return;
        }

        if($ProductStockEvent->getMoveOrder() === null)
        {
            $this->logger
                ->notice('Не создаем приход: Статус складской заявки является Completed «Выдан по месту назначения», но заявка не имеет заказ (перемещение не по заказу)',
                    [self::class.':'.__LINE__]);

            return;
        }

        $this->logger
            ->info('Создаем приход на склад при перемещении продукции между складами по заказу когда заявка Completed «Выдан по месту назначения»',
                [self::class.':'.__LINE__]);


        $User = $this->userByUserProfile
            ->forProfile($ProductStockEvent->getProfile())
            ->findUser();

        $WarehouseProductStockDTO = new WarehouseProductStockDTO($User);
        $ProductStockEvent->getDto($WarehouseProductStockDTO);

        /** Присваиваем заявке - склад назначения */
        $WarehouseProductStockDTO->setProfile($ProductStockEvent->getMove()?->getDestination());
        $this->WarehouseProductStockHandler->handle($WarehouseProductStockDTO);

        $this->logger
            ->info('Создали приход на склад при перемещении продукции между складами по заказу когда заявка Completed «Выдан по месту назначения»',
                [
                    self::class.':'.__LINE__,
                    'profile' => $ProductStockEvent->getMove()?->getDestination(),
                ]
            );

    }
}
