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

namespace BaksDev\DeliveryTransport\Entity\Transport\Parameter;


use BaksDev\Core\Entity\EntityEvent;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Модификаторы событий DeliveryTransportParameter */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_transport_parameter')]
class DeliveryTransportParameter extends EntityEvent
{
    public const TABLE = 'delivery_transport_parameter';

    /** ID события */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'parameter', targetEntity: DeliveryTransportEvent::class)]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private DeliveryTransportEvent $event;

    /** Грузоподъемность, кг */
    #[Assert\NotBlank]
    #[ORM\Column(type: Kilogram::TYPE)]
    private Kilogram $carrying;

    /** Длина (Глубина), см  */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 500)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $length;

    /** Ширина, см */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 500)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $width;

    /** Высота, см */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 300)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $height;

    /** Объем, см3 */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    #[ORM\Column(type: Types::INTEGER)]
    private int $size;

    public function __construct(DeliveryTransportEvent $event)
    {
        $this->event = $event;
    }

    public function __toString(): string
    {
        return (string) $this->event;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if ($dto instanceof DeliveryTransportParameterInterface) {

            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof DeliveryTransportParameterInterface || $dto instanceof self)
        {
            /** Объем, см3 */
            $this->size = $dto->getWidth() * $dto->getLength() * $dto->getHeight();

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


}