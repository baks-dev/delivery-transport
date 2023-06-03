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

namespace BaksDev\DeliveryTransport\Entity\Transport\Trans;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Перевод DeliveryTransportTrans */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_transport_trans')]
#[ORM\Index(columns: ['name'])]
class DeliveryTransportTrans extends EntityEvent
{
    public const TABLE = 'delivery_transport_trans';

    /** Связь на событие */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: DeliveryTransportEvent::class, inversedBy: 'translate')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private DeliveryTransportEvent $event;

    /** Локаль */
    #[Assert\NotBlank]
    #[Assert\Length(max: 2)]
    #[ORM\Id]
    #[ORM\Column(type: Locale::TYPE)]
    private readonly Locale $local;

    /** Название */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING)]
    private string $name;


    public function __construct(DeliveryTransportEvent $event)
    {
        $this->event = $event;
    }

    public function getDto($dto): mixed
    {
        if ($dto instanceof DeliveryTransportTransInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof DeliveryTransportTransInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function name(Locale $locale): ?string
    {
        if ($this->local->getValue() === $locale->getValue())
        {
            return $this->name;
        }

        return null;
    }
}
