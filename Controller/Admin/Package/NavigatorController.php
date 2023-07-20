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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackage;
use BaksDev\DeliveryTransport\Repository\Package\PackageOrderGeocode\PackageOrderGeocodeInterface;
use BaksDev\DeliveryTransport\Repository\Package\PackageWarehouseGeocode\PackageWarehouseGeocodeInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RoleSecurity('ROLE_DELIVERY_PACKAGE_NAVIGATOR')]
final class NavigatorController extends AbstractController
{
    #[Route('/admin/delivery/navigator/{id}', name: 'admin.package.navigator', methods: ['GET', 'POST'])]
    public function index(
        #[MapEntity] DeliveryPackage $DeliveryPackage,
        PackageWarehouseGeocodeInterface $warehouseGeocode,
        PackageOrderGeocodeInterface $orderGeocode,
    ): Response {

        /**
         * Определяем геолокацию склада погрузки (начальную точку).
         */
        $geoWarehouse = $warehouseGeocode->fetchPackageWarehouseGeocodeAssociative($DeliveryPackage->getId());

        if (!$geoWarehouse)
        {
            $this->addFlash('danger', 'Невозможно определить геолокацию склада погрузки');
            return $this->redirectToRoute('DeliveryTransport:admin.package.index');
        }

        $goeData[] = $geoWarehouse['latitude'].','.$geoWarehouse['longitude'];

        /**
         * Получаем все заказы в путевом листе с геолокацией.
         */
        $geoOrders = $orderGeocode->fetchAllPackageOrderGeocodeAssociative($DeliveryPackage->getEvent());

        foreach ($geoOrders as $order)
        {
            $geo = $order['latitude'].','.$order['longitude'];

            if (!in_array($geo, $goeData, true))
            {
                $goeData[] = $geo;
            }
        }

        $goeData[] = $geoWarehouse['latitude'].','.$geoWarehouse['longitude'];

        return new RedirectResponse('https://yandex.ru/maps/?rtext='.implode('~', $goeData).'&rtt=auto');
    }
}
