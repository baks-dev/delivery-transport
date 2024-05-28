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
use BaksDev\Core\Messenger\MessageDispatchInterface;
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
use BaksDev\Products\Stocks\Messenger\Stocks\SubProductStocksTotal\SubProductStocksTotalAndReserveMessage;
use BaksDev\Products\Stocks\Repository\ProductStocksById\ProductStocksByIdInterface;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateProductStocksTotalByDelivery
{
    private ProductStocksByIdInterface $productStocks;

    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;
    private MessageDispatchInterface $messageDispatch;

    public function __construct(
        ProductStocksByIdInterface $productStocks,
        EntityManagerInterface $entityManager,
        LoggerInterface $deliveryTransportLogger,
        MessageDispatchInterface $messageDispatch
    )
    {
        $this->productStocks = $productStocks;
        $this->entityManager = $entityManager;
        $this->logger = $deliveryTransportLogger;
        $this->messageDispatch = $messageDispatch;
    }

    /**
     * Обновляет складской резерв + наличие при изменении заявки на Delivery «Доставка»
     */
    public function __invoke(ProductStockMessage $message): void
    {
        /** TODO: */
        return;

        $this->entityManager->clear();

        $ProductStockEvent = $this->entityManager
            ->getRepository(ProductStockEvent::class)
            ->find($message->getEvent());

        if(!$ProductStockEvent)
        {
            return;
        }


        // Если Статус складской заявки не является "ДОСТАВКА"
        if(!$ProductStockEvent || !$ProductStockEvent->getStatus()->equals(ProductStockStatusDelivery::class))
        {
            return;
        }

        // Получаем всю продукцию в заявке со статусом Delivery
        $products = $this->productStocks->getProductsByProductStocksStatus($message->getId(), ProductStockStatusDelivery::class);

        if($products)
        {
            /** @var ProductStockProduct $product */
            foreach($products as $key => $product)
            {
                $ProductStockTotal = $this->entityManager
                    ->getRepository(ProductStockTotal::class)
                    ->findOneBy(
                        [
                            'profile' => $ProductStockEvent->getProfile(),
                            'product' => $product->getProduct(),
                            'offer' => $product->getOffer(),
                            'variation' => $product->getVariation(),
                            'modification' => $product->getModification(),
                        ]
                    );

                if(!$ProductStockTotal)
                {
                    $this->logger->error('Ошибка при обновлении складских остатков. Не удалось получить остаток продукции.',
                        [
                            __FILE__.':'.__LINE__,
                            'profile' => $ProductStockEvent->getProfile(),
                            'product' => $product->getProduct(),
                            'offer' => $product->getOffer(),
                            'variation' => $product->getVariation(),
                            'modification' => $product->getModification(),
                        ]
                    );

                    throw new InvalidArgumentException('Ошибка при обновлении складских остатков');
                }

                if(
                    $ProductStockTotal->getTotal() < $product->getTotal() ||
                    $ProductStockTotal->getReserve() < $product->getTotal()
                )
                {

                    $this->logger->error('Ошибка при обновлении складских остатков. Не достаточно баланса на складе.',
                        [
                            __FILE__.':'.__LINE__,
                            'profile' => $ProductStockEvent->getProfile(),
                            'product' => $product->getProduct(),
                            'offer' => $product->getOffer(),
                            'variation' => $product->getVariation(),
                            'modification' => $product->getModification(),
                            'total' => $ProductStockTotal->getTotal(),
                            'reserve' => $ProductStockTotal->getReserve(),
                            'sub' => $product->getTotal(),
                        ]
                    );

                    throw new InvalidArgumentException('Ошибка при обновлении складских остатков');

                }


                /** Снимаем резерв и наличие со склада (переход на баланс транспорта доставки) */
                for($i = 1; $i <= $product->getTotal(); $i++)
                {
                    $SubProductStocksTotalMessage = new SubProductStocksTotalAndReserveMessage(
                        $ProductStockEvent->getProfile(),
                        $product->getProduct(),
                        $product->getOffer(),
                        $product->getVariation(),
                        $product->getModification()
                    );

                    $this->messageDispatch->dispatch($SubProductStocksTotalMessage, transport: 'products-stocks');
                }

                //$ProductStockTotal->subTotal($product->getTotal());
                //$ProductStockTotal->subReserve($product->getTotal());

                $this->logger->info('Перевели баланс продукции '.$key.' со склада на баланс транспорта (Доставка)',
                    [
                        __FILE__.':'.__LINE__,
                        'profile' => $ProductStockEvent->getProfile(),
                        'product' => $product->getProduct(),
                        'offer' => $product->getOffer(),
                        'variation' => $product->getVariation(),
                        'modification' => $product->getModification(),
                        'total' => $product->getTotal(),
                    ]
                );
            }

            $this->entityManager->flush();
        }
    }
}
