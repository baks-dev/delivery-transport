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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageOrderProducts;

use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\Orders\Order\Entity as EntityOrder;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use BaksDev\Products\Stocks\Entity\Products\ProductStockProduct;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;
use Doctrine\DBAL\Connection;

final class PackageOrderProductsRepository implements PackageOrderProductsInterface
{
    private Connection $connection;

    public function __construct(
        Connection $connection,
    ) {
        $this->connection = $connection;
    }

    /**
     * Метод получает продукт и его параметрами упаковки.
     */
    public function fetchParameterProductAssociative(
        ProductUid $product,
        ?ProductOfferConst $offer,
        ?ProductVariationConst $variation,
        ?ProductModificationConst $modification
    ): array|bool {
        $qb = $this->connection->createQueryBuilder();

        $qb->addSelect('parameter.size');
        $qb->addSelect('parameter.weight');

        $qb->from(DeliveryPackageProductParameter::TABLE, 'parameter');

        $qb->where('parameter.product = :product AND 
            (parameter.offer IS NULL OR parameter.offer =  :offer) AND
            (parameter.variation IS NULL OR parameter.variation = :variation) AND
            (parameter.modification IS NULL OR parameter.modification = :modification)
        ');

        $qb->setParameter('product', $product, ProductUid::TYPE);
        $qb->setParameter('offer', $offer, ProductOfferConst::TYPE);
        $qb->setParameter('variation', $variation, ProductVariationConst::TYPE);
        $qb->setParameter('modification', $modification, ProductModificationConst::TYPE);


        return $qb->fetchAssociative();
    }

    /**
     * Метод получает всю продукцию в заказе с параметрами упаковки.
     */
    public function fetchAllPackageStocksProductsAssociative(ProductStockEventUid $event): array|bool
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->from(ProductStockProduct::TABLE, 'product');

        $qb->where('product.event = :event');
        $qb->setParameter('event', $event, ProductStockEventUid::TYPE);

        $qb->addSelect('product.total');

        $qb->addSelect('parameter.size');
        $qb->addSelect('parameter.weight');

        $qb->leftJoin(
            'product',
            DeliveryPackageProductParameter::TABLE,
            'parameter',
            'parameter.product = product.product AND 
            (parameter.offer IS NULL OR parameter.offer =  product.offer) AND
            (parameter.variation IS NULL OR parameter.variation = product.variation) AND
            (parameter.modification IS NULL OR parameter.modification = product.modification)
            '
        );

        return $qb->fetchAllAssociative();
    }

    /**
     * Метод получает всю продукцию в заказе с параметрами упаковки.
     */
    public function fetchAllPackageOrderProductsAssociative(OrderUid $order): array|bool
    {
        $qb = $this->connection->createQueryBuilder();

        //$qb->select('product.*');

        $qb->from(EntityOrder\Order::TABLE, 'ord');

        $qb->join(
            'ord',
            EntityOrder\Event\OrderEvent::TABLE,
            'event',
            'event.id = ord.event'
        );

        $qb->join(
            'event',
            EntityOrder\Products\OrderProduct::TABLE,
            'product',
            'product.event = ord.event'
        );

        $qb->addSelect('price.total');
        $qb->join(
            'product',
            EntityOrder\Products\Price\OrderPrice::TABLE,
            'price',
            'price.product = product.id'
        );

        $qb->join(
            'product',
            ProductEvent::TABLE,
            'product_event',
            'product_event.id = product.product'
        );

        //$qb->addSelect('product_offer.const AS product_offer_const');
        $qb->leftJoin(
            'product',
            ProductOffer::TABLE,
            'product_offer',
            'product_offer.id = product.offer'
        );

        //$qb->addSelect('product_variation.const AS product_variation_const');
        $qb->leftJoin(
            'product',
            ProductVariation::TABLE,
            'product_variation',
            'product_variation.id = product.variation'
        );

        //$qb->addSelect('product_modification.const AS product_modification_const');
        $qb->leftJoin(
            'product',
            ProductModification::TABLE,
            'product_modification',
            'product_modification.id = product.modification'
        );

        $qb->addSelect('parameter.size');
        $qb->addSelect('parameter.weight');
        $qb->leftJoin(
            'product',
            DeliveryPackageProductParameter::TABLE,
            'parameter',
            'parameter.product = product_event.main AND 
            (parameter.offer IS NULL OR parameter.offer = product_offer.const) AND
            (parameter.variation IS NULL OR parameter.variation = product_variation.const) AND
            (parameter.modification IS NULL OR parameter.modification = product_modification.const)
            '
        );

        $qb->where('ord.id = :order');
        $qb->setParameter('order', $order, OrderUid::TYPE);
        return $qb->fetchAllAssociative();
    }
}
