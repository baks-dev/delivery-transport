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

namespace BaksDev\DeliveryTransport\Controller\Admin\ProductParameter;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\DeliveryTransport\Entity\ProductParameter\DeliveryPackageProductParameter;
use BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter\DeliveryPackageProductParameterDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter\DeliveryPackageProductParameterForm;
use BaksDev\DeliveryTransport\UseCase\Admin\ProductParameter\DeliveryPackageProductParameterHandler;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByConstInterface;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\ConstId\ProductModificationConst;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_DELIVERY_PACKAGE_PARAMETER_EDIT')]
final class EditController extends AbstractController
{
    #[Route('/admin/delivery/package/parameter/{product}/{offer}/{variation}/{modification}', name: 'admin.parameter.edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        DeliveryPackageProductParameterHandler $DeliveryPackageProductParameterHandler,
        ProductDetailByConstInterface $productDetail,
        #[ParamConverter(ProductUid::class)] $product,
        #[ParamConverter(ProductOfferConst::class)] $offer = null,
        #[ParamConverter(ProductVariationConst::class)] $variation = null,
        #[ParamConverter(ProductModificationConst::class)] $modification = null,
    ): Response {

        $DeliveryPackageProductParameterDTO = new DeliveryPackageProductParameterDTO();
        $DeliveryPackageProductParameterDTO
            ->setProduct($product)
            ->setOffer($offer)
            ->setVariation($variation)
            ->setModification($modification);

        $ProductStockParameter = $entityManager->getRepository(DeliveryPackageProductParameter::class)
            ->findOneBy([
                'product' => $DeliveryPackageProductParameterDTO->getProduct(),
                'offer' => $DeliveryPackageProductParameterDTO->getOffer(),
                'variation' => $DeliveryPackageProductParameterDTO->getVariation(),
                'modification' => $DeliveryPackageProductParameterDTO->getModification()
            ]);

        if ($ProductStockParameter)
        {
            $ProductStockParameter->getDto($DeliveryPackageProductParameterDTO);
        }

        // Форма
        $form = $this->createForm(
            DeliveryPackageProductParameterForm::class,
            $DeliveryPackageProductParameterDTO,
            ['action' => $this->generateUrl(
                'DeliveryTransport:admin.parameter.edit',
                [
                    'product' => $DeliveryPackageProductParameterDTO->getProduct(),
                    'offer' => $DeliveryPackageProductParameterDTO->getOffer(),
                    'variation' => $DeliveryPackageProductParameterDTO->getVariation(),
                    'modification' => $DeliveryPackageProductParameterDTO->getModification()
                ]
            )]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->has('product_stock_parameter'))
        {
            if ($form->isValid())
            {
                $ProductStockParameter = $DeliveryPackageProductParameterHandler->handle($DeliveryPackageProductParameterDTO);

                if ($ProductStockParameter instanceof DeliveryPackageProductParameter)
                {
                    $this->addFlash('success', 'admin.success.update', 'admin.product.parameter');

                    return $this->redirectToRoute('DeliveryTransport:admin.parameter.index');
                }

                $this->addFlash('danger', 'admin.danger.new', 'admin.product.parameter', $ProductStockParameter);

                return $this->redirectToReferer();
            }
            
            return new JsonResponse(
                [
                    'type' => 'danger',
                    'header' => 'Параметры упаковки',
                    'message' => (string) $form->getErrors(),
                    'status' => 400,
                ],
                400
            );
        }

        $card = $productDetail->fetchProductDetailByConstAssociative($product, $offer, $variation, $modification);

        return $this->render(['form' => $form->createView(), 'card' => $card]);
    }
}
