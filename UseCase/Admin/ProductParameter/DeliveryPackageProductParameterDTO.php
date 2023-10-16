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

namespace BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter;

use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameterInterface;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryPackageProductParameter */
final class DeliveryPackageProductParameterDTO implements DeliveryPackageProductParameterInterface
{
    /**
     * ID продукта.
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductUid $product;

    /**
     * Постоянный уникальный идентификатор ТП
     */
    #[Assert\Uuid]
    private readonly ?ProductOfferConst $offer;

    /**
     * Постоянный уникальный идентификатор варианта.
     */
    #[Assert\Uuid]
    private readonly ?ProductVariationConst $variation;

    /**
     * Постоянный уникальный идентификатор модификации.
     */
    #[Assert\Uuid]
    private readonly ?ProductModificationConst $modification;

    /**
     * Вес, кг.
     */
    #[Assert\NotBlank]
    private Kilogram $weight;

    /**
     * Длина (Глубина), см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 500)]
    private int $length;

    /**
     * Ширина, см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 500)]
    private int $width;

    /**
     * Высота, см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 300)]
    private int $height;



    /**
     * ID продукта.
     */
    public function getProduct(): ProductUid
    {
        return $this->product;
    }

    public function setProduct(ProductUid $product): self
    {
        $this->product = $product;
        return $this;
    }

    /**
     * Постоянный уникальный идентификатор ТП
     */
    public function getOffer(): ?ProductOfferConst
    {
        return $this->offer;
    }

    public function setOffer(?ProductOfferConst $offer): self
    {
        if (!(new ReflectionProperty(self::class, 'offer'))->isInitialized($this))
        {
            $this->offer = $offer;
        }

        return $this;
    }

    /**
     *  Постоянный уникальный идентификатор варианта.
     */
    public function getVariation(): ?ProductVariationConst
    {
        return $this->variation;
    }

    public function setVariation(?ProductVariationConst $variation): self
    {
        if (!(new ReflectionProperty(self::class, 'variation'))->isInitialized($this))
        {
            $this->variation = $variation;
        }

        return $this;
    }

    /**
     * Постоянный уникальный идентификатор модификации.
     */
    public function getModification(): ?ProductModificationConst
    {
        return $this->modification;
    }

    public function setModification(?ProductModificationConst $modification): self
    {
        if (!(new ReflectionProperty(self::class, 'modification'))->isInitialized($this))
        {
            $this->modification = $modification;
        }

        return $this;
    }

    /**
     * Вес, кг.
     */
    public function getWeight(): Kilogram
    {
        return $this->weight;
    }

    public function setWeight(Kilogram $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * Длина (Глубина), см
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
     *  Ширина, см
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
     * Высота, см
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
