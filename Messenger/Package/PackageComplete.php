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

namespace BaksDev\DeliveryTransport\Messenger\Package;

use BaksDev\DeliveryTransport\Repository\Package\ExistPackageProductStocks\ExistPackageProductStocksInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageByProductStocks\PackageByProductStocksInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedPackageDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedPackageHandler;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PackageComplete
{
    private LoggerInterface $logger;
    private ExistPackageProductStocksInterface $existPackageProductStocks;

    private PackageByProductStocksInterface $packageByProductStocks;

    private CompletedPackageHandler $completedPackageHandler;


    public function __construct(
        LoggerInterface $deliveryTransportLogger,
        ExistPackageProductStocksInterface $existPackageProductStocks,
        PackageByProductStocksInterface $packageByProductStocks,
        CompletedPackageHandler $completedPackageHandler,

    )
    {
        $this->logger = $deliveryTransportLogger;
        $this->existPackageProductStocks = $existPackageProductStocks;
        $this->packageByProductStocks = $packageByProductStocks;
        $this->completedPackageHandler = $completedPackageHandler;
    }

    /**
     *  Если все заказы выданы - меняем статус путевого листа на "ВЫПОЛНЕН"
     */
    public function __invoke(ProductStockMessage $message): void
    {

        /** TODO: */
        return;

        /** Получаем упаковку с данным заказом */
        $DeliveryPackage = $this->packageByProductStocks
            ->getDeliveryPackageByProductStock($message->getId());

        /* Проверяем, имеются ли еще не выполненные заявки в доставке */
        if($DeliveryPackage && $this->existPackageProductStocks->isExistStocksDeliveryPackage($DeliveryPackage->getId()) === false)
        {
            /** Если все заказы выданы - меняем статус путевого листа на "ВЫПОЛНЕН"   */
            $CompletedPackageDTO = new CompletedPackageDTO($DeliveryPackage->getEvent());
            $this->completedPackageHandler->handle($CompletedPackageDTO);

            $this->logger->error('Сделали отметку о полном выполнение путевого листа',
                [
                    self::class.':'.__LINE__,
                    'event' => $DeliveryPackage->getEvent()
                ]
            );
        }
    }
}
