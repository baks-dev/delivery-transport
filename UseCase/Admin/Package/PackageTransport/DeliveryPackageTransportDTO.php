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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport;

use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransportInterface;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryPackageTransport */
final class DeliveryPackageTransportDTO implements DeliveryPackageTransportInterface
{
    /**
     * Идентификатор поставки.
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private DeliveryPackageUid $package;

    /**
     * Идентификатор транспорта.
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private DeliveryTransportUid $transport;

    /**
     * Дата погрузки транспорта.
     */
    #[Assert\NotBlank]
    private int $date;

    /**
     * Заполняемый объем транспорта.
     */
    private int $size = 0;

    /**
     * Заполняемая грузоподъемность, кг.
     */
    private int $carrying = 0;

    /**
     * Очередь на погрузку (Общее количество км в пути).
     */
    private int $traveled = 0;

    /**
     * Идентификатор поставки.
     */
    public function getPackage(): DeliveryPackageUid
    {
        return $this->package;
    }

    public function setPackage(DeliveryPackage|DeliveryPackageUid $package): void
    {
        $this->package = $package instanceof DeliveryPackage ? $package->getId() : $package;
    }

    /**
     * Идентификатор транспорта.
     */
    public function getTransport(): DeliveryTransportUid
    {
        return $this->transport;
    }

    public function setTransport(DeliveryTransport|DeliveryTransportUid $transport): void
    {
        $this->transport = $transport instanceof DeliveryTransport ? $transport->getId() : $transport;
    }

    /**
     * Дата погрузки транспорта.
     */
    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): void
    {
        $this->date = $date;
    }

    /**
     * Заполняемый объем транспорта.
     */
    public function addSize(int $size): void
    {
        $this->size += $size;
    }

    public function subSize(int $size): void
    {
        $this->size -= $size;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * Заполняемая грузоподъемность, кг.
     */
    public function addCarrying(int $carrying): void
    {
        $this->carrying += $carrying;
    }

    public function subCarrying(int $carrying): void
    {
        $this->carrying -= $carrying;
    }

    /**
     * Carrying.
     */
    public function getCarrying(): int
    {
        return $this->carrying;
    }

    public function setCarrying(int $carrying): void
    {
        $this->carrying = $carrying;
    }

    /**
     * Очередь на погрузку (Общее количество км в пути).
     */
    public function setTraveled(int $traveled): void
    {
        $this->traveled = $traveled;
    }

    public function getTraveled(): int
    {
        return $this->traveled;
    }
}
