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

namespace BaksDev\DeliveryTransport\Messenger\Package\ProductStocks;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\DeliveryTransport\Type\OrderStatus\OrderStatusDelivery;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusDelivery;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Products\ProductStockProduct;
use BaksDev\Products\Stocks\Entity\ProductStockTotal;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Repository\ProductStocksById\ProductStocksByIdInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateOrderStatusByDelivery
{
    //private ProductStocksByIdInterface $productStocks;

    private EntityManagerInterface $entityManager;

    private CurrentOrderEventInterface $currentOrderEvent;

    private OrderStatusHandler $OrderStatusHandler;

    private CentrifugoPublishInterface $CentrifugoPublish;

    private LoggerInterface $logger;

    public function __construct(
        //ProductStocksByIdInterface $productStocks,
        EntityManagerInterface $entityManager,
        CurrentOrderEventInterface $currentOrderEvent,
        OrderStatusHandler $OrderStatusHandler,
        CentrifugoPublishInterface $CentrifugoPublish,
        LoggerInterface $deliveryTransportLogger,
    ) {
        //$this->productStocks = $productStocks;
        $this->entityManager = $entityManager;
        $this->currentOrderEvent = $currentOrderEvent;
        $this->OrderStatusHandler = $OrderStatusHandler;
        $this->CentrifugoPublish = $CentrifugoPublish;
        $this->logger = $deliveryTransportLogger;

    }

    /**
     * Обновляет статус заказа при погрузке (Сменяется статус заявки на Delivery «Доставка»)
     */
    public function __invoke(ProductStockMessage $message): void
    {

        /** TODO: */
        return;

        $ProductStockEvent = $this->entityManager->getRepository(ProductStockEvent::class)
            ->find($message->getEvent());

        // Если Статус складской заявки не является "ДОСТАВКА"
        if(
            !$ProductStockEvent ||
            $ProductStockEvent->getMoveOrder() ||
            $ProductStockEvent->getStatus()->equals(new ProductStockStatusDelivery()) === false
        ) {
            return;
        }

        /**
         * Получаем событие заказа.
         */
        $OrderEvent = $this->currentOrderEvent
            ->order($ProductStockEvent->getOrder())
            ->getCurrentOrderEvent();

        if($OrderEvent)
        {
            /** Обновляем статус заказа на "Доставка" (Delivery) */
            $OrderStatusDTO = new OrderStatusDTO(
                OrderStatusDelivery::class,
                $OrderEvent->getId(),
                $ProductStockEvent->getProfile()
            );
            $this->OrderStatusHandler->handle($OrderStatusDTO);

            // Отправляем сокет для скрытия заказа у других менеджеров
            $this->CentrifugoPublish
                ->addData(['order' => (string) $ProductStockEvent->getOrder()])
                ->addData(['profile' => (string) $ProductStockEvent->getProfile()])
                ->send('orders');


            $this->logger->info(
                'Обновили статус заказа на "Доставка" (Delivery)',
                [
                    __FILE__.':'.__LINE__,
                    'order' => (string) $ProductStockEvent->getOrder(),
                    'profile' => (string) $ProductStockEvent->getProfile()
                ]
            );

        }

    }
}
