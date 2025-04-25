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

namespace BaksDev\DeliveryTransport\Entity\Package\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\Modify\DeliveryPackageModify;
use BaksDev\DeliveryTransport\Entity\Package\Stocks\DeliveryPackageStocks;
use BaksDev\DeliveryTransport\Type\Package\Event\DeliveryPackageEventUid;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatus;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* DeliveryPackageEvent */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_package_event')]
class DeliveryPackageEvent extends EntityEvent
{
    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: DeliveryPackageEventUid::TYPE)]
    private DeliveryPackageEventUid $id;

    /** ID DeliveryPackage */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: DeliveryPackageUid::TYPE, nullable: false)]
    private ?DeliveryPackageUid $main = null;

    /** Модификатор */
    #[ORM\OneToOne(targetEntity: DeliveryPackageModify::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private DeliveryPackageModify $modify;


    /** Заявки для перемещения */
    #[ORM\OneToMany(targetEntity: DeliveryPackageStocks::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private Collection $stock;


    /** Статус поставки */
    #[Assert\NotBlank]
    #[ORM\Column(type: DeliveryPackageStatus::TYPE)]
    private DeliveryPackageStatus $status;

    public function __construct()
    {
        $this->id = new DeliveryPackageEventUid();
        $this->modify = new DeliveryPackageModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): DeliveryPackageEventUid
    {
        return $this->id;
    }

    public function setMain(DeliveryPackageUid|DeliveryPackage $main): void
    {
        $this->main = $main instanceof DeliveryPackage ? $main->getId() : $main;
    }

    public function getMain(): ?DeliveryPackageUid
    {
        return $this->main;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof DeliveryPackageEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof DeliveryPackageEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}
