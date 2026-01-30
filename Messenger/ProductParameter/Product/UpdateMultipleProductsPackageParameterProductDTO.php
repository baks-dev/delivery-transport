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

namespace BaksDev\DeliveryTransport\Messenger\ProductParameter\Product;

use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateMultipleProductsPackageParameterProductDTO
{
    /** Категория */
    #[Assert\NotBlank]
    private ?CategoryProductUid $category = null;

    /** Постоянный уникальный идентификатор ТП */
    private string|null $offer = null;

    /** Постоянный уникальный идентификатор варианта */
    private string|null $variation = null;

    /** Постоянный уникальный идентификатор модификации */
    private string|null $modification = null;


    /**
     * Category (не маппится с сущности на DTO, но используется в форме)
     */
    public function getCategory(): ?CategoryProductUid
    {
        return $this->category;
    }

    public function setCategory(?CategoryProductUid $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Offer
     */
    public function getOffer(): ?string
    {
        return $this->offer;
    }

    public function setOffer(ProductOfferConst|string|null $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    /**
     * Variation
     */
    public function getVariation(): ProductVariationConst|string|null
    {
        return $this->variation;
    }

    public function setVariation(null|string|ProductVariationConst $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    /**
     * Modification
     */
    public function getModification(): ProductModificationConst|string|null
    {
        return $this->modification;
    }

    public function setModification(null|string|ProductModificationConst $modification): self
    {
        $this->modification = $modification;
        return $this;
    }
}