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

namespace BaksDev\DeliveryTransport\Entity\Package;

use InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use BaksDev\Core\Entity\EntityState;
use Symfony\Component\Validator\Constraints as Assert;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;

/* DeliveryPackage */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_package_transport')]
class DeliveryPackageTransport extends EntityState
{
    public const TABLE = 'delivery_package_transport';

    /**
     * Идентификатор поставки.
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: DeliveryPackageUid::TYPE)]
    private readonly DeliveryPackageUid $package;

    /**
     * Идентификатор транспорта.
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: DeliveryTransportUid::TYPE, nullable: false)]
    private readonly DeliveryTransportUid $transport;

    /**
     * Дата погрузки транспорта (timestamp).
     */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\Column(name: 'date_package', type: Types::INTEGER)]
    private readonly int $date;

    /**
     * Заполняемый объем транспорта.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $size = 0;

    /**
     * Заполняемая грузоподъемность, кг.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $carrying = 0;

    /**
     * Очередь на погрузку (Общее количество км в пути).
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $interval = 0;

    public function __construct(
        DeliveryPackageUid|DeliveryPackage $package,
        DeliveryTransportUid|DeliveryTransport $transport,
        int $date
    ) {
        $this->package = $package;
        $this->transport = $transport;
        $this->date = $date;
    }

    public function getDto($dto): mixed
    {
        if ($dto instanceof DeliveryPackageTransportInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof DeliveryPackageTransportInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Package.
     */
    public function getPackage(): DeliveryPackageUid
    {
        return $this->package;
    }

    public function setInterval(int $interval): void
    {
        $this->interval = $interval;
    }
}
