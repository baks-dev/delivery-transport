<?php
/*
 * Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\Messenger\ProductParameter\Parameters;

use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameterInterface;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMultipleProductsPackageParameterParametersDTO implements DeliveryPackageProductParameterInterface
{
    /**
     * Вес, кг.
     */
    #[Assert\NotBlank(groups: ['parameters'])]
    private Kilogram $weight;

    /**
     * Длина (Глубина), см
     */
    #[Assert\NotBlank(groups: ['parameters'])]
    #[Assert\Range(min: 1, groups: ['parameters'])]
    private int $length;

    /**
     * Ширина, см
     */
    #[Assert\NotBlank(groups: ['parameters'])]
    #[Assert\Range(min: 1, groups: ['parameters'])]
    private int $width;

    /**
     * Высота, см
     */
    #[Assert\NotBlank(groups: ['parameters'])]
    #[Assert\Range(min: 1, groups: ['parameters'])]
    private int $height;

    /**
     * Машиноместо
     */
    #[Assert\NotBlank(groups: ['parameters'])]
    #[Assert\Range(min: 1, groups: ['parameters'])]
    private int $package = 1;


    /**
     * Вес, кг.
     */
    public function getWeight(): Kilogram
    {
        return $this->weight;
    }

    public function setWeight(Kilogram $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    /**
     * Длина (Глубина), см
     */
    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    /**
     *  Ширина, см
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Высота, см
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Package
     */
    public function getPackage(): int
    {
        return $this->package;
    }

    public function setPackage(?int $package): self
    {
        if(null === $package)
        {
            return $this;
        }

        $this->package = $package;
        return $this;
    }
}