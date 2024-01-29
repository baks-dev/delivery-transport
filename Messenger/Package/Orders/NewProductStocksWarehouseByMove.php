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

namespace BaksDev\DeliveryTransport\Messenger\Package\Orders;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusCompleted;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Repository\ProductStocksById\ProductStocksByIdInterface;
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
    ) {

        $this->entityManager = $entityManager;
        $this->WarehouseProductStockHandler = $WarehouseProductStockHandler;
        $this->userByUserProfile = $userByUserProfile;
        $this->logger = $deliveryTransportLogger;
    }

    /**
     * Создаем приход на склад при перемещении продукции между складами когда выполняется заявка (Completed)
     */
    public function __invoke(ProductStockMessage $message): void
    {
        $ProductStockEvent = $this->entityManager->getRepository(ProductStockEvent::class)
            ->find($message->getEvent());

        // Если Статус складской заявки не является "Выдан по месту назначения"
        if (!$ProductStockEvent || $ProductStockEvent->getStatus()->equals(new ProductStockStatusCompleted()) === false)
        {
            return;
        }

        if ($ProductStockEvent->getMoveOrder())
        {
            $User = $this->userByUserProfile->findUserByProfile($ProductStockEvent->getProfile());

            $WarehouseProductStockDTO = new WarehouseProductStockDTO($User);
            $ProductStockEvent->getDto($WarehouseProductStockDTO);

            /** Присваиваем приходу - склад назначения */
            $WarehouseProductStockDTO->setProfile($ProductStockEvent->getMove()->getDestination());
            $this->WarehouseProductStockHandler->handle($WarehouseProductStockDTO);

            $this->logger->info('Создали заявку на приход при перемещении продукции',
                [
                    __FILE__.':'.__LINE__,
                    'profile' => $ProductStockEvent->getMove()->getDestination(),
                ]
            );
        }
    }
}
