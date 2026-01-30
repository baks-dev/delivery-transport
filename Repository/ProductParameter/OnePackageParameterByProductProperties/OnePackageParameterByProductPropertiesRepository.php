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

namespace BaksDev\DeliveryTransport\Repository\ProductParameter\OnePackageParameterByProductProperties;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Product;
use Doctrine\DBAL\Types\Types;

final class OnePackageParameterByProductPropertiesRepository implements OnePackageParameterByProductPropertiesInterface
{
    private ?string $offer = null;

    private ?string $variation = null;

    private ?string $modification = null;

    public function __construct(private readonly ORMQueryBuilder $ORMQueryBuilder) {}

    public function forOffer(?string $offer): self
    {
        $this->offer = $offer;
        return $this;
    }

    public function forVariation(?string $variation): self
    {
        $this->variation = $variation;
        return $this;
    }

    public function forModification(?string $modification): self
    {
        $this->modification = $modification;
        return $this;
    }

    
    /**
     * Получаем параметры упаковки любого первого продукта, соответствующего указанным значениям offer, variation и
     * modification
     */
    public function findOne(): ?DeliveryPackageProductParameter
    {
        $orm = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $orm
            ->from(DeliveryPackageProductParameter::class, 'delivery_package_product_parameters')
            ->select('delivery_package_product_parameters');

        $orm
            ->join(
                Product::class,
                'product',
                'WITH',
                'product.id = delivery_package_product_parameters.product'
            );

        if(false === empty($offer))
        {
            $orm
                ->join(
                    ProductOffer::class,
                    'product_offer',
                    'WITH',
                    'product_offer.event = product.event AND product_offer.value = :offer'
                )
                ->setParameter('offer', $this->offer, Types::STRING);
        }

        if(false === empty($variation))
        {
            $orm
                ->join(
                    ProductVariation::class,
                    'product_variation',
                    'WITH',
                    'product_variation.offer = product_offer.id AND product_variation.value = :variation'
                )
                ->setParameter('variation', $this->variation, Types::STRING);
        }

        if(false === empty($modification))
        {
            $orm
                ->join(
                    ProductModification::class,
                    'product_modification',
                    'WITH',
                    'product_modification.variation = product_variation.id AND
                product_modification.value = :modification'
                )
                ->setParameter('modification', $this->modification, Types::STRING);
        }

        $orm->setMaxResults(1);

        return $orm->getOneOrNullResult();
    }
}