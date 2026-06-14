<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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
 *
 */

declare(strict_types=1);

namespace BaksDev\DeliveryTransport\Messenger\ProductStocks;

use BaksDev\Centrifugo\BaksDevCentrifugoBundle;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\DeliveryTransport\Type\OrderStatus\OrderStatusDelivery;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusDelivery;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Messenger\LockOrder\OrderUnlockMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusDTO;
use BaksDev\Orders\Order\UseCase\Admin\Status\OrderStatusHandler;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Repository\CurrentProductStocks\CurrentProductStocksInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Если статус заявки Delivery «Доставка» - Обновляет статус заказа на Delivery «Доставка (погружен в транспорт)»
 *
 * @note Снимает блокировку с заказа
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 0)]
final readonly class UpdateOrderStatusByDeliveryDispatcher
{
    public function __construct(
        #[Target('deliveryTransportLogger')] private LoggerInterface $logger,
        private MessageDispatchInterface $messageDispatch,
        private CurrentProductStocksInterface $CurrentProductStocksRepository,
        private CurrentOrderEventInterface $CurrentOrderEventRepository,
        private UserByUserProfileInterface $UserByUserProfileRepository,
        private OrderStatusHandler $OrderStatusHandler,
        private ?CentrifugoPublishInterface $CentrifugoPublish = null,
    ) {}

    public function __invoke(ProductStockMessage $message): void
    {
        $ProductStockEvent = $this->CurrentProductStocksRepository
            ->getCurrentEvent($message->getId());

        if(false === ($ProductStockEvent instanceof ProductStockEvent))
        {
            $this->logger->critical(
                sprintf('delivery-transport: Событие складской заявки %s не было найдено', $message->getEvent()),
                [self::class.':'.__LINE__, var_export($message, true)]
            );
            return;
        }

        /** Если статус заявки не является Delivery «Доставка» - завершаем обработчик */
        if(false === $ProductStockEvent->equalsProductStockStatus(ProductStockStatusDelivery::class))
        {
            return;
        }

        if(false === $ProductStockEvent->isInvariable())
        {
            $this->logger->warning(
                'Складская заявка не может определить ProductStocksInvariable',
                [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        $UserProfileUid = $ProductStockEvent->getInvariable()->getProfile();

        /**
         * Получаем событие заказа.
         */
        $OrderEvent = $this->CurrentOrderEventRepository
            ->forOrder($ProductStockEvent->getOrder())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                message: 'products-stocks: Не найдено OrderEvent',
                context: [self::class.':'.__LINE__, var_export($message, true)]
            );
            return;
        }

        /**
         * Обновляем статус заказа на Delivery «Доставка (погружен в транспорт)»
         */

        $OrderStatusDTO = new OrderStatusDTO(
            OrderStatusDelivery::class,
            $OrderEvent->getId(),
        );


        /**
         * Присваиваем ответственное лицо если указан FIXED
         */
        if(true === ($ProductStockEvent->getFixed() instanceof UserProfileUid))
        {
            $User = $this->UserByUserProfileRepository
                ->forProfile($ProductStockEvent->getFixed())
                ->find();

            if(false === ($User instanceof User))
            {
                $this->logger->critical(
                    'orders-order: Пользователь ответственного лица не найден',
                    [self::class.':'.__LINE__, 'fixed' => (string) $ProductStockEvent->getFixed()],
                );

                return;
            }

            $OrderStatusDTO
                ->getModify()
                ->setUsr($User->getId());
        }


        $Order = $this->OrderStatusHandler->handle($OrderStatusDTO);

        if(false === ($Order instanceof Order))
        {
            $this->logger->critical(
                sprintf('orders-order: Ошибка %s при обновлении статуса заказа на Delivery «Доставка»', $Order),
                [self::class.':'.__LINE__, var_export($Order, true)],
            );

            return;
        }

        $this->logger->info(
            message: sprintf('%s: Обновили статус заказа на %s',
                $OrderEvent->getPostingNumber() ?? $OrderEvent->getOrderNumber(),
                $OrderStatusDTO->getStatus()->getOrderStatusValue()
            ),
            context: [
                self::class.':'.__LINE__,
                'OrderUid' => (string) $ProductStockEvent->getOrder(),
                'UserProfileUid' => (string) $UserProfileUid,
            ],
        );

        /** Синхронно снимаем блокировку с заказа */

        $OrderUnlockMessage = new OrderUnlockMessage(
            id: $ProductStockEvent->getOrder(),
            context: self::class.':'.__LINE__
        );

        $this->messageDispatch->dispatch(
            message: $OrderUnlockMessage,
            transport: 'orders-order',
        );

        if(true === class_exists(BaksDevCentrifugoBundle::class))
        {
            /** Отправляем сокет для скрытия заказа */
            $socket = $this->CentrifugoPublish
                ->addData([
                    'order' => (string) $ProductStockEvent->getOrder(),
                    'profile' => false,
                    'context' => self::class.':'.__LINE__
                ])
                ->send('orders');

            if($socket && $socket->isError())
            {
                $this->logger->critical(
                    message: 'products-stocks: Ошибка при отправке информации о блокировке в Centrifugo',
                    context: [
                        $socket->getMessage(),
                        'number' => $ProductStockEvent->getNumber(),
                        'main' => $ProductStockEvent->getMain(),
                        self::class.':'.__LINE__,
                    ],
                );
            }
        }
    }
}
