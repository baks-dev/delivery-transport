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

declare(strict_types = 1);

namespace BaksDev\DeliveryTransport\Messenger\ProductParameter;

use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter\DeliveryPackageProductParameterDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter\DeliveryPackageProductParameterHandler;
use BaksDev\Products\Product\Repository\ProductsByValues\ProductsByValuesRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;

final readonly class UpdateMultipleProductsPackageParametersDispatcher
{
    public function __construct(
        #[Target('deliveryTransportLogger')] private LoggerInterface $Logger,
        private ProductsByValuesRepository $ProductsByValuesRepository,
        private DeliveryPackageProductParameterHandler $deliveryPackageProductParameterHandler
    ) {}

    public function __invoke(UpdateMultipleProductsPackageParameterDTO $updateMultipleProductsPackageParameterDTO): void
    {
        /** Получаем свойства, по которым фильтруем продукты */
        $updateMultipleProductsPackageParameterProductDTO = $updateMultipleProductsPackageParameterDTO->getProduct();
        $updateMultipleProductsPackageParameterParametersDTO = $updateMultipleProductsPackageParameterDTO->getParameters();


        /** Получаем все продукты с указанными свойствами */
        $productsByValuesResults = $this->ProductsByValuesRepository
            ->forCategory($updateMultipleProductsPackageParameterProductDTO->getCategory())
            ->forOfferValue($updateMultipleProductsPackageParameterProductDTO->getOffer())
            ->forVariationValue($updateMultipleProductsPackageParameterProductDTO->getVariation())
            ->forModificationValue($updateMultipleProductsPackageParameterProductDTO->getModification())
            ->findAll();

        if(false === $productsByValuesResults->valid())
        {
            $this->Logger->critical(
                'delivery-transport: Ошибка при получении продукта по указанным свойствам',
                [self::class.':'.__LINE__, var_export($updateMultipleProductsPackageParameterDTO, true)],
            );

            return;
        }

        foreach($productsByValuesResults as $productsByValuesResult)
        {
            $deliveryPackageProductParameterDTO = new DeliveryPackageProductParameterDTO()
                ->setProduct($productsByValuesResult->getProduct())
                ->setOffer($productsByValuesResult->getProductOfferConst())
                ->setVariation($productsByValuesResult->getProductVariationConst())
                ->setModification($productsByValuesResult->getProductModificationConst())
                ->setWeight($updateMultipleProductsPackageParameterParametersDTO->getWeight())
                ->setLength($updateMultipleProductsPackageParameterParametersDTO->getLength())
                ->setWidth($updateMultipleProductsPackageParameterParametersDTO->getWidth())
                ->setHeight($updateMultipleProductsPackageParameterParametersDTO->getHeight())
                ->setPackage($updateMultipleProductsPackageParameterParametersDTO->getPackage());

            $handle = $this->deliveryPackageProductParameterHandler->handle($deliveryPackageProductParameterDTO);
            if(false === $handle instanceof DeliveryPackageProductParameter)
            {
                $this->Logger->critical(
                    sprintf('delivery-transport: Ошибка %s при обновлении параметров упаковки', $handle),
                    [self::class.':'.__LINE__, var_export($handle, true)],
                );

                continue;
            }

            $this->Logger->info(
                'delivery-transport: Успешно обновлены параметры упаковки',
                [self::class.':'.__LINE__, var_export($handle, true)],
            );
        }
    }
}