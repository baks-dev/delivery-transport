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

namespace BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\DeliveryTransport\Messenger\Transport\DeliveryTransportMessage;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;

final class DeliveryPackageProductParameterHandler extends AbstractHandler
{

    /** @see DeliveryPackageProductParameter */
    public function handle(
        DeliveryPackageProductParameterDTO $command,
    ): string|DeliveryPackageProductParameter
    {
        /** Валидация DTO  */
        $this->setCommand($command);

        $ProductStockParameter = $this->getRepository(DeliveryPackageProductParameter::class)
            ->findOneBy([
                'product' => $command->getProduct(),
                'offer' => $command->getOffer(),
                'variation' => $command->getVariation(),
                'modification' => $command->getModification(),
            ]);

        if(!$ProductStockParameter)
        {
            $ProductStockParameter = new DeliveryPackageProductParameter();
            $this->persist($ProductStockParameter);
        }

        $ProductStockParameter->setEntity($command);
        $this->validatorCollection->add($ProductStockParameter);


        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new DeliveryTransportMessage(new DeliveryTransportUid(), new  DeliveryTransportEventUid()),
            transport: 'delivery-transport',
        );

        return $ProductStockParameter;
    }


}
