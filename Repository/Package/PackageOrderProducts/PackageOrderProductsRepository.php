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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Entity\Products\Price\OrderPrice;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Stocks\Entity\Stock\Products\ProductStockProduct;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;
use InvalidArgumentException;

final  class PackageOrderProductsRepository implements PackageOrderProductsInterface
{
    private ProductUid|false $product = false;

    private ProductOfferConst|false $offer = false;

    private ProductVariationConst|false $variation = false;

    private ProductModificationConst|false $modification = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function product(ProductUid $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function offerConst(ProductOfferConst|string|null|false $offer): self
    {
        if(empty($offer))
        {
            $this->offer = false;
            return $this;
        }

        if(is_string($offer))
        {
            $offer = new ProductOfferConst($offer);
        }

        $this->offer = $offer;

        return $this;
    }

    public function variationConst(ProductVariationConst|string|null|false $variation): self
    {
        if(empty($variation))
        {
            $this->variation = false;
            return $this;
        }

        if(is_string($variation))
        {
            $variation = new ProductVariationConst($variation);
        }

        $this->variation = $variation;

        return $this;
    }

    public function modificationConst(ProductModificationConst|string|null|false $modification): self
    {
        if(empty($modification))
        {
            $this->modification = false;
            return $this;
        }

        if(is_string($modification))
        {
            $modification = new ProductModificationConst($modification);
        }

        $this->modification = $modification;
        return $this;
    }


    /**
     * Метод получает продукт и его параметрами упаковки.
     */
    public function find(): array|bool
    {
        if(false === ($this->product instanceof ProductUid))
        {
            throw new InvalidArgumentException('Invalid Argument Product');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('parameter.size')
            ->addSelect('parameter.weight')
            ->from(DeliveryPackageProductParameter::class, 'parameter')
            ->where('parameter.product = :product')
            ->setParameter(
                key: 'product',
                value: $this->product,
                type: ProductUid::TYPE
            );

        if($this->offer)
        {
            $dbal
                ->andWhere('parameter.offer =  :offer')
                ->setParameter(
                    key: 'offer',
                    value: $this->offer,
                    type: ProductOfferConst::TYPE);
        }
        else
        {
            $dbal->andWhere('parameter.offer IS NULL');
        }


        if($this->variation)
        {
            $dbal
                ->andWhere('parameter.variation = :variation')
                ->setParameter(
                    key: 'variation',
                    value: $this->variation,
                    type: ProductVariationConst::TYPE
                );
        }
        else
        {
            $dbal->andWhere('parameter.variation IS NULL');
        }

        if($this->modification)
        {
            $dbal
                ->andWhere('parameter.modification = :modification')
                ->setParameter(
                    key: 'modification',
                    value: $this->modification,
                    type: ProductModificationConst::TYPE
                );
        }
        else
        {
            $dbal->andWhere('parameter.modification IS NULL');
        }


        return $dbal->fetchAssociative();
    }

    /**
     * Метод получает всю продукцию в заказе с параметрами упаковки.
     */
    public function fetchAllPackageStocksProductsAssociative(ProductStockEventUid $event): array|bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(ProductStockProduct::class, 'product');
        $dbal->where('product.event = :event');
        $dbal->setParameter('event', $event, ProductStockEventUid::TYPE);

        $dbal->addSelect('product.total');
        $dbal->addSelect('product_package.size');
        $dbal->addSelect('product_package.weight');

        $dbal->leftJoin(
            'product',
            DeliveryPackageProductParameter::class,
            'product_package',
            'product_package.product = product.product AND 
                    
                    (
                        (product_offer.const IS NOT NULL AND product_package.offer = product_offer.const) OR 
                        (product_offer.const IS NULL AND product_package.offer IS NULL)
                    )
                    
                    AND
                     
                    (
                        (product_variation.const IS NOT NULL AND product_package.variation = product_variation.const) OR 
                        (product_variation.const IS NULL AND product_package.variation IS NULL)
                    )
                     
                   AND
                   
                   (
                        (product_modification.const IS NOT NULL AND product_package.modification = product_modification.const) OR 
                        (product_modification.const IS NULL AND product_package.modification IS NULL)
                   )
            '
        );

        return $dbal->fetchAllAssociative();
    }

    /**
     * Метод получает всю продукцию в заказе с параметрами упаковки.
     */
    public function fetchAllPackageOrderProductsAssociative(OrderUid $order): array|bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(Order::class, 'ord');

        $dbal->join(
            'ord',
            OrderEvent::class,
            'event',
            'event.id = ord.event'
        );

        $dbal->join(
            'event',
            OrderProduct::class,
            'product',
            'product.event = ord.event'
        );

        $dbal->addSelect('price.total');
        $dbal->join(
            'product',
            OrderPrice::class,
            'price',
            'price.product = product.id'
        );

        $dbal->join(
            'product',
            ProductEvent::class,
            'product_event',
            'product_event.id = product.product'
        );

        //$dbal->addSelect('product_offer.const AS product_offer_const');
        $dbal->leftJoin(
            'product',
            ProductOffer::class,
            'product_offer',
            'product_offer.id = product.offer'
        );

        //$dbal->addSelect('product_variation.const AS product_variation_const');
        $dbal->leftJoin(
            'product',
            ProductVariation::class,
            'product_variation',
            'product_variation.id = product.variation'
        );

        //$dbal->addSelect('product_modification.const AS product_modification_const');
        $dbal->leftJoin(
            'product',
            ProductModification::class,
            'product_modification',
            'product_modification.id = product.modification'
        );

        $dbal->addSelect('product_package.size');
        $dbal->addSelect('product_package.weight');
        $dbal->leftJoin(
            'product',
            DeliveryPackageProductParameter::class,
            'product_package',
            'product_package.product = product_event.main  AND 
                    
                    (
                        (product_offer.const IS NOT NULL AND product_package.offer = product_offer.const) OR 
                        (product_offer.const IS NULL AND product_package.offer IS NULL)
                    )
                    
                    AND
                     
                    (
                        (product_variation.const IS NOT NULL AND product_package.variation = product_variation.const) OR 
                        (product_variation.const IS NULL AND product_package.variation IS NULL)
                    )
                     
                   AND
                   
                   (
                        (product_modification.const IS NOT NULL AND product_package.modification = product_modification.const) OR 
                        (product_modification.const IS NULL AND product_package.modification IS NULL)
                   )
            '
        );

        $dbal
            ->where('ord.id = :order')
            ->setParameter('order', $order, OrderUid::TYPE);

        return $dbal->fetchAllAssociative();
    }
}
