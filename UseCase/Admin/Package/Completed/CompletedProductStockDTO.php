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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed;

use BaksDev\Core\Type\UidType\Uid;
use BaksDev\DeliveryTransport\Type\ProductStockStatus\ProductStockStatusCompleted;
use BaksDev\Products\Stocks\Entity\Event\ProductStockEventInterface;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see ProductStockEvent */
final class CompletedProductStockDTO implements ProductStockEventInterface
{
    /** Идентификатор */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly ProductStockEventUid $id;

    /** Ответственное лицо (Профиль пользователя) */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;

    /** Статус заявки - Выдана клиенту */
    #[Assert\NotBlank]
    private readonly ProductStockStatus $status;


    public function __construct(ProductStockEventUid $id, UserProfileUid $profile)
    {
        $this->status = new ProductStockStatus(new ProductStockStatusCompleted());
        $this->id = $id;
        $this->profile = $profile;
    }

    public function getEvent(): ?Uid
    {
        return $this->id;
    }

    /** Ответственное лицо (Профиль пользователя) */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    /** Статус заявки - Доставляется */
    public function getStatus(): ProductStockStatus
    {
        return $this->status;
    }

}
