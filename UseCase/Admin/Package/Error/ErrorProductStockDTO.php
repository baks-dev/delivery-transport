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

declare(strict_types=1);

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\Error;

use BaksDev\Core\Type\UidType\Uid;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusError;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEventInterface;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductStockEvent */
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
        $this->status = new ProductStockStatus(new ProductStockStatusError());
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
