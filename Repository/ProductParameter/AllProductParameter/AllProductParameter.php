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

namespace BaksDev\DeliveryTransport\Repository\ProductParameter\AllProductParameter;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\DeliveryTransport\Forms\ProductParameter\ProductParameterFilterInterface;
use BaksDev\Elastic\Api\Index\ElasticGetIndex;
use BaksDev\Elastic\BaksDevElasticBundle;
use BaksDev\Products\Category\Entity as CategoryEntity;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Product\Entity;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;

final class AllProductParameter implements AllProductParameterInterface
{

    private PaginatorInterface $paginator;

    private DBALQueryBuilder $DBALQueryBuilder;

    private ?SearchDTO $search = null;

    private ?ProductFilterDTO $filter = null;
    private ?ElasticGetIndex $elasticGetIndex;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
        ?ElasticGetIndex $elasticGetIndex = null
    )
    {

        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
        $this->elasticGetIndex = $elasticGetIndex;
    }


    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(ProductFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }


    /**
     * Метод возвращает пагинатор ProductParameter c параметрами упаковки
     */
    public function fetchAllProductParameterAssociative(): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->select('product.id');
        $qb->addSelect('product.event');

        $qb->from(Entity\Product::class, 'product');

        $qb->join(
            'product',
            Entity\Event\ProductEvent::class,
            'product_event',
            'product_event.id = product.event'
        );

        $qb
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                Entity\Trans\ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local'
            );

        /* ProductInfo */

        $qb->addSelect('product_info.url');

        $qb->leftJoin(
            'product_event',
            Entity\Info\ProductInfo::class,
            'product_info',
            'product_info.product = product.id'
        );


        /* Торговое предложение */

        $qb
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.const as product_offer_const')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->leftJoin(
                'product_event',
                Entity\Offers\ProductOffer::class,
                'product_offer',
                'product_offer.event = product_event.id'
            );

        if($this->filter?->getOffer())
        {
            $qb->andWhere('product_offer.value = :offer');
            $qb->setParameter('offer', $this->filter->getOffer());
        }


        /* Цена торгового предложения */
        $qb->leftJoin(
            'product_offer',
            Entity\Offers\Price\ProductOfferPrice::class,
            'product_offer_price',
            'product_offer_price.offer = product_offer.id'
        );

        /* Тип торгового предложения */
        $qb
            ->addSelect('category_offer.reference as product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryEntity\Offers\ProductCategoryOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /* Множественные варианты торгового предложения */

        $qb
            ->addSelect('product_offer_variation.value as product_variation_value')
            ->addSelect('product_offer_variation.const as product_variation_const')
            ->addSelect('product_offer_variation.postfix as product_variation_postfix')
            ->leftJoin(
                'product_offer',
                Entity\Offers\Variation\ProductVariation::class,
                'product_offer_variation',
                'product_offer_variation.offer = product_offer.id'
            );


        if($this->filter?->getVariation())
        {
            $qb->andWhere('product_offer_variation.value = :variation');
            $qb->setParameter('variation', $this->filter->getVariation());
        }


        /* Цена множественного варианта */
        $qb->leftJoin(
            'category_offer_variation',
            Entity\Offers\Variation\Price\ProductVariationPrice::class,
            'product_variation_price',
            'product_variation_price.variation = product_offer_variation.id'
        );

        /* Тип множественного варианта торгового предложения */
        $qb->addSelect('category_offer_variation.reference as product_variation_reference');
        $qb->leftJoin(
            'product_offer_variation',
            CategoryEntity\Offers\Variation\ProductCategoryVariation::TABLE,
            'category_offer_variation',
            'category_offer_variation.id = product_offer_variation.category_variation'
        );

        /* Модификация множественного варианта */
        $qb
            ->addSelect('product_offer_modification.value as product_modification_value')
            ->addSelect('product_offer_modification.const as product_modification_const')
            ->addSelect('product_offer_modification.postfix as product_modification_postfix')
            ->leftJoin(
                'product_offer_variation',
                Entity\Offers\Variation\Modification\ProductModification::class,
                'product_offer_modification',
                'product_offer_modification.variation = product_offer_variation.id '
            );


        if($this->filter?->getModification())
        {
            $qb->andWhere('product_offer_modification.value = :modification');
            $qb->setParameter('modification', $this->filter->getModification());
        }


        /* Получаем тип модификации множественного варианта */
        $qb->addSelect('category_offer_modification.reference as product_modification_reference');
        $qb->leftJoin(
            'product_offer_modification',
            CategoryEntity\Offers\Variation\Modification\ProductCategoryModification::class,
            'category_offer_modification',
            'category_offer_modification.id = product_offer_modification.category_modification'
        );


        /* Артикул продукта */


        $qb->addSelect(
            '
					CASE
					   WHEN product_offer_modification.article IS NOT NULL THEN product_offer_modification.article
					   WHEN product_offer_variation.article IS NOT NULL THEN product_offer_variation.article
					   WHEN product_offer.article IS NOT NULL THEN product_offer.article
					   WHEN product_info.article IS NOT NULL THEN product_info.article
					   ELSE NULL
					END AS product_article
				'
        );

        /* Фото продукта */

        $qb->leftJoin(
            'product_event',
            Entity\Photo\ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            Entity\Offers\Variation\Image\ProductVariationImage::class,
            'product_offer_variation_image',
            'product_offer_variation_image.variation = product_offer_variation.id AND product_offer_variation_image.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            Entity\Offers\Image\ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $qb->addSelect(
            "
			CASE
			   WHEN product_offer_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".Entity\Offers\Variation\Image\ProductVariationImage::TABLE."' , '/', product_offer_variation_image.name)
			   WHEN product_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".Entity\Offers\Image\ProductOfferImage::TABLE."' , '/', product_offer_images.name)
			   WHEN product_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".Entity\Photo\ProductPhoto::TABLE."' , '/', product_photo.name)
			   ELSE NULL
			END AS product_image
		"
        );

        /* Флаг загрузки файла CDN */
        $qb->addSelect('
			CASE
			   WHEN product_offer_variation_image.name IS NOT NULL THEN
					product_offer_variation_image.ext
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.ext
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.ext
			   ELSE NULL
			END AS product_image_ext
		');

        /* Флаг загрузки файла CDN */
        $qb->addSelect('
			CASE
			   WHEN product_offer_variation_image.name IS NOT NULL THEN
					product_offer_variation_image.cdn
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.cdn
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.cdn
			   ELSE NULL
			END AS product_image_cdn
		');

        /* Категория */
        $qb->join(
            'product_event',
            Entity\Category\ProductCategory::class,
            'product_event_category',
            'product_event_category.event = product_event.id AND product_event_category.root = true'
        );

        if($this->filter?->getCategory())
        {
            $qb->andWhere('product_event_category.category = :category');
            $qb->setParameter('category', $this->filter->getCategory(), ProductCategoryUid::TYPE);
        }

        $qb->join(
            'product_event_category',
            CategoryEntity\ProductCategory::class,
            'category',
            'category.id = product_event_category.category'
        );

        $qb->addSelect('category_trans.name AS category_name');

        $qb->leftJoin(
            'category',
            CategoryEntity\Trans\ProductCategoryTrans::class,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );


        /** Длина, см  */
        $qb->addSelect('product_parameter.length AS product_parameter_length');
        /** Ширина, см */
        $qb->addSelect('product_parameter.width AS product_parameter_width');
        /** Высота, см */
        $qb->addSelect('product_parameter.height AS product_parameter_height');
        /** Вес, кг */
        $qb->addSelect('product_parameter.weight AS product_parameter_weight');
        /** Объем, см3 */
        $qb->addSelect('product_parameter.size AS product_parameter_size');


        $qb->leftJoin(
            'product_offer_modification',
            DeliveryPackageProductParameter::class,
            'product_parameter',
            'product_parameter.product = product.id AND 
            (product_parameter.offer IS NULL OR product_parameter.offer = product_offer.const) AND
            (product_parameter.variation IS NULL OR product_parameter.variation = product_offer_variation.const) AND
            (product_parameter.modification IS NULL OR product_parameter.modification = product_offer_modification.const)
            
        ');

        $qb->addOrderBy('product_parameter.id', 'DESC');

        if($this->search?->getQuery())
        {

            /** Поиск по модификации */
            $result = $this->elasticGetIndex ? $this->elasticGetIndex->handle(ProductModification::class, $this->search->getQuery(), 1) : false;

            if($result)
            {

                $counter = $result['hits']['total']['value'];

                if($counter)
                {

                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $qb
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('product_offer_modification.id', array_column($data, "id"));

                    return $this->paginator->fetchAllAssociative($qb);
                }

                /** Поиск по продукции */
                $result = $this->elasticGetIndex->handle(Entity\Product::class, $this->search->getQuery(), 1);

                $counter = $result['hits']['total']['value'];

                if($counter)
                {
                    /** Идентификаторы */
                    $data = array_column($result['hits']['hits'], "_source");

                    $qb
                        ->createSearchQueryBuilder($this->search)
                        ->addSearchInArray('product.id', array_column($data, "id"));

                    return $this->paginator->fetchAllAssociative($qb);
                }
            }


            $qb
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('account.id')
                ->addSearchEqualUid('account.event')
                ->addSearchLike('product_trans.name')
                //->addSearchLike('product_trans.preview')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_offer_modification.article')
                ->addSearchLike('product_offer_variation.article');

            $qb->addOrderBy('product.event', 'DESC');

        }


        return $this->paginator->fetchAllAssociative($qb);

    }
}
