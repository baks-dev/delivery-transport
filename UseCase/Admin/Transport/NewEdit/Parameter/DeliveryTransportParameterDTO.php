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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit\Parameter;

use BaksDev\DeliveryTransport\Entity\Transport\Parameter\DeliveryTransportParameterInterface;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryTransportParameter */
final class DeliveryTransportParameterDTO implements DeliveryTransportParameterInterface
{
    /**
     * Грузоподъемность, кг.
     */
    #[Assert\NotBlank]
    private Kilogram $carrying;

    /**
     * Длина (Глубина), см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    private int $length;

    /**
     * Ширина, см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    private int $width;

    /**
     * Высота, см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    private int $height;

    /**
     * Грузоподъемность, кг
     */
    public function getCarrying(): Kilogram
    {
        return $this->carrying;
    }

    public function setCarrying(Kilogram $carrying): void
    {
        $this->carrying = $carrying;
    }

    /**
     * Длина (Глубина), см.
     */
    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    /**
     *  Ширина, см.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * Высота, см.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): void
    {
        $this->height = $height;
    }
}
