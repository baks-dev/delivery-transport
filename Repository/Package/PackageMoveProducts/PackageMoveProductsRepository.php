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

namespace BaksDev\DeliveryTransport\Repository\Package\PackageMoveProducts;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\Products\Stocks\Entity\Stock\Products\ProductStockProduct;
use BaksDev\Products\Stocks\Type\Event\ProductStockEventUid;

final class PackageMoveProductsRepository implements PackageMoveProductsInterface
{

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод получает всю продукцию в заявке для перемещения с параметрами упаковки
     */
    public function fetchAllPackageMoveProductsAssociative(ProductStockEventUid $event): array|bool
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('product.total')
            ->from(ProductStockProduct::class, 'product')
            ->where('product.event = :event')
            ->setParameter('event', $event, ProductStockEventUid::TYPE);

        $qb
            ->addSelect('parameter.size')
            ->addSelect('parameter.weight')
            ->leftJoin(
                'product',
                DeliveryPackageProductParameter::class,
                'parameter',
                'parameter.product = product.product AND 
            (parameter.offer IS NULL OR parameter.offer = product.offer) AND
            (parameter.variation IS NULL OR parameter.variation = product.variation) AND
            (parameter.modification IS NULL OR parameter.modification = product.modification)
            ');

        return $qb->fetchAllAssociative();
    }
}