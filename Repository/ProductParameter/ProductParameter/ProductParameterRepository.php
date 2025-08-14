<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\Repository\ProductParameter\ProductParameter;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;


final class ProductParameterRepository implements ProductParameterInterface
{
    private ProductUid $product;

    private ProductOfferConst|false $offerConst = false;

    private ProductVariationConst|false $variationConst = false;

    private ProductModificationConst|false $modificationConst = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forProduct(Product|ProductUid|string $product): self
    {
        if(is_string($product))
        {
            $product = new ProductUid($product);
        }

        if($product instanceof Product)
        {
            $product = $product->getId();
        }

        $this->product = $product;

        return $this;
    }

    public function forOfferConst(ProductOfferConst|string|null|false $offerConst): self
    {
        if(is_null($offerConst) || $offerConst === false)
        {
            $this->offerConst = false;
            return $this;
        }

        if(is_string($offerConst))
        {
            $offerConst = new ProductOfferConst($offerConst);
        }

        $this->offerConst = $offerConst;

        return $this;
    }

    public function forVariationConst(ProductVariationConst|string|null|false $variationConst): self
    {
        if(is_null($variationConst) || $variationConst === false)
        {
            $this->variationConst = false;
            return $this;
        }

        if(is_string($variationConst))
        {
            $variationConst = new ProductVariationConst($variationConst);
        }

        $this->variationConst = $variationConst;

        return $this;
    }

    public function forModificationConst(ProductModificationConst|string|null|false $modificationConst): self
    {
        if(is_null($modificationConst) || $modificationConst === false)
        {
            $this->modificationConst = false;
            return $this;
        }

        if(is_string($modificationConst))
        {
            $modificationConst = new ProductModificationConst($modificationConst);
        }

        $this->modificationConst = $modificationConst;

        return $this;
    }

    /**
     * Метод возвращает параметры упаковки продукта
     */
    public function find(): array|bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('param.weight')
            ->addSelect('param.length')
            ->addSelect('param.width')
            ->addSelect('param.height')
            ->addSelect('param.size')
            ->addSelect('param.package')
            ->from(DeliveryPackageProductParameter::class, 'param')
            ->andWhere('param.product = :product')
            ->setParameter('product', $this->product, ProductUid::TYPE);

        if($this->offerConst)
        {
            $dbal
                ->andWhere('param.offer = :offer')
                ->setParameter('offer', $this->offerConst, ProductOfferConst::TYPE);


            if($this->variationConst)
            {
                $dbal
                    ->andWhere('param.variation = :variation')
                    ->setParameter('variation', $this->variationConst, ProductVariationConst::TYPE);

                if($this->modificationConst)
                {
                    $dbal
                        ->andWhere('param.modification = :modification')
                        ->setParameter('modification', $this->modificationConst, ProductModificationConst::TYPE);
                }
                else
                {
                    $dbal->andWhere('param.modification IS NULL');
                }

            }
            else
            {
                $dbal->andWhere('param.variation IS NULL');
                $dbal->andWhere('param.modification IS NULL');
            }

        }
        else
        {
            $dbal->andWhere('param.offer IS NULL');
            $dbal->andWhere('param.variation IS NULL');
            $dbal->andWhere('param.modification IS NULL');
        }

        $result = $dbal
            ->enableCache('delivery-transport', '1 day')
            ->fetchAllAssociative();


        return $result ?: false;
    }
}