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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\Error;

use BaksDev\Core\Type\UidType\Uid;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEventInterface;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** @see MaterialStockEvent */
final class ErrorProductStockDTO implements ProductStockEventInterface
{
    /** Идентификатор */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly ProductStockEventUid $id;

    /** Статус заявки - ошибка доставки ("Не вмещается по габаритам или весу") */
    #[Assert\NotBlank]
    private readonly ProductStockStatus $status;

    /** Комментарий */
    #[Assert\NotBlank]
    private string $comment;

    public function __construct(ProductStockEventUid $id)
    {
        $this->status = new ProductStockStatus(new ProductStockStatus\ProductStockStatusError());
        $this->id = $id;
        $this->comment = 'Заказ имеет превышение по весу либо габаритам. Для доставки его необходимо разделить!';
    }

    public function getEvent(): ?Uid
    {
        return $this->id;
    }

    /** Статус заявки - "Не вмещается по габаритам или весу" */
    public function getStatus(): ProductStockStatus
    {
        return $this->status;
    }

    /**
     * Comment.
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
