<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

final readonly class PackageOrderProductsRepository implements PackageOrderProductsInterface
{

    public function __construct(private DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод получает продукт и его параметрами упаковки.
     */
    public function fetchParameterProductAssociative(
        ProductUid $product,
        ?ProductOfferConst $offer,
        ?ProductVariationConst $variation,
        ?ProductModificationConst $modification
    ): array|bool
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->addSelect('parameter.size');
        $dbal->addSelect('parameter.weight');

        $dbal->from(DeliveryPackageProductParameter::class, 'parameter');

        $dbal->where('parameter.product = :product AND 
            (parameter.offer IS NULL OR parameter.offer =  :offer) AND
            (parameter.variation IS NULL OR parameter.variation = :variation) AND
            (parameter.modification IS NULL OR parameter.modification = :modification)
        ');

        $dbal->setParameter('product', $product, ProductUid::TYPE);
        $dbal->setParameter('offer', $offer, ProductOfferConst::TYPE);
        $dbal->setParameter('variation', $variation, ProductVariationConst::TYPE);
        $dbal->setParameter('modification', $modification, ProductModificationConst::TYPE);


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
        $dbal->addSelect('parameter.size');
        $dbal->addSelect('parameter.weight');

        $dbal->leftJoin(
            'product',
            DeliveryPackageProductParameter::class,
            'parameter',
            'parameter.product = product.product AND 
            (parameter.offer IS NULL OR parameter.offer =  product.offer) AND
            (parameter.variation IS NULL OR parameter.variation = product.variation) AND
            (parameter.modification IS NULL OR parameter.modification = product.modification)
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

        $dbal->addSelect('parameter.size');
        $dbal->addSelect('parameter.weight');
        $dbal->leftJoin(
            'product',
            DeliveryPackageProductParameter::class,
            'parameter',
            'parameter.product = product_event.main AND 
            (parameter.offer IS NULL OR parameter.offer = product_offer.const) AND
            (parameter.variation IS NULL OR parameter.variation = product_variation.const) AND
            (parameter.modification IS NULL OR parameter.modification = product_modification.const)
            '
        );

        $dbal->where('ord.id = :order');
        $dbal->setParameter('order', $order, OrderUid::TYPE);
        return $dbal->fetchAllAssociative();
    }
}
