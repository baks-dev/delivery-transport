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

namespace BaksDev\DeliveryTransport\Entity\ProductParameter;

use BaksDev\Core\Entity\EntityState;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Stocks\Type\Parameters\ProductStockParameterUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* ProductParameters */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_package_product_parameters')]
class DeliveryPackageProductParameter extends EntityState
{
    public const TABLE = 'delivery_package_product_parameters';

    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ProductStockParameterUid::TYPE)]
    private ProductStockParameterUid $id;

    /** ID продукта */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductUid::TYPE)]
    private ProductUid $product;

    /** Постоянный уникальный идентификатор ТП */
    #[ORM\Column(type: ProductOfferConst::TYPE, nullable: true)]
    private ?ProductOfferConst $offer;

    /** Постоянный уникальный идентификатор варианта */
    #[ORM\Column(type: ProductVariationConst::TYPE, nullable: true)]
    private ?ProductVariationConst $variation;

    /** Постоянный уникальный идентификатор модификации */
    #[ORM\Column(type: ProductModificationConst::TYPE, nullable: true)]
    private ?ProductModificationConst $modification;

    /**
     * Вес, кг
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Kilogram::TYPE)]
    private Kilogram $weight;

    /**
     * Длина (Глубина), см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 32767)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $length;

    /**
     * Ширина, см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 32767)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $width;

    /**
     * Высота, см
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 32767)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $height;

    /**
     * Объем, см3
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    #[ORM\Column(type: Types::INTEGER)]
    private int $size;

    public function __construct()
    {
        $this->id = new ProductStockParameterUid();

    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ProductStockParameterUid
    {
        return $this->id;
    }


    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof DeliveryPackageProductParameterInterface)
        {

            /** Переводим вес граммы в килограммы   */
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof DeliveryPackageProductParameterInterface || $dto instanceof self)
        {

            /** Объем, см3 */
            $this->size = $dto->getWidth() * $dto->getLength() * $dto->getHeight();

            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}
