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

namespace BaksDev\DeliveryTransport\Controller\Admin\Package;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\DeliveryTransport\Forms\Package\Admin\DeliveryPackageFilterDTO;
use BaksDev\DeliveryTransport\Forms\Package\Admin\DeliveryPackageFilterForm;
use BaksDev\DeliveryTransport\Repository\Package\AllDeliveryPackage\AllDeliveryPackageInterface;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RoleSecurity('ROLE_DELIVERY_PACKAGE')]
final class IndexController extends AbstractController
{
    /** Список путевых листов */
    #[Route('/admin/delivery/packages/{page<\d+>}', name: 'admin.package.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        AllDeliveryPackageInterface $allDeliveryPackage,
        #[ParamConverter(DeliveryPackageUid::class)] $package = null,
        int $page = 0,
    ): Response {
        // Поиск
        $search = new SearchDTO();
        $searchForm = $this->createForm(SearchForm::class, $search);
        $searchForm->handleRequest($request);

        // Фильтр
        $ROLE_ADMIN = $this->isGranted('ROLE_ADMIN');
        $filter = new DeliveryPackageFilterDTO($request, $ROLE_ADMIN ? null : $this->getProfileUid());
        $filterForm = $this->createForm(DeliveryPackageFilterForm::class, $filter);
        $filterForm->handleRequest($request);
        
        // Получаем список
        $DeliveryPackage = $allDeliveryPackage->fetchAllDeliveryPackageAssociative($search, $filter);

        return $this->render(
            [
                'query' => $DeliveryPackage,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
                'package' => $package
            ]
        );
    }
}
