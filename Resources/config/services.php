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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $namespace = 'BaksDev\DeliveryTransport';

    $services->load($namespace.'\\', __DIR__.'/../../')
        ->exclude(__DIR__.'/../../{Controller,Entity,Resources,Type,Tests,*DTO.php,*Message.php}');

    // Services

    $services->load($namespace.'\Controller\\', __DIR__.'/../../Controller')
        ->tag('controller.service_arguments')
        ->exclude(__DIR__.'/../../Controller/**/*Test.php')
    ;


    /** Статусы заказа */
    $services->load($namespace.'\Type\OrderStatus\\', __DIR__.'/../../Type/OrderStatus');

    /** Статусы складской заявки */
    $services->load($namespace.'\Type\ProductStockStatus\\', __DIR__.'/../../Type/ProductStockStatus');

    /** Статусы погрузки */
    $services->load($namespace.'\Type\Package\Status\DeliveryPackageStatus\\', __DIR__.'/../../Type/Package/Status/DeliveryPackageStatus');




//    $services->set(DeliveryPackageStatusCollection::class)
//        ->args([tagged_iterator('baks.delivery.package.status')])
//    ;




//    $services->load($namespace.'\Repository\\', __DIR__.'/../../Repository')
//        ->exclude(__DIR__.'/../../Repository/**/*DTO.php')
//    ;
//
//    $services->load($namespace.'\Listeners\\', __DIR__.'/../../Listeners');


//
//    $services->load($namespace.'\Forms\\', __DIR__.'/../../Forms')
//        ->exclude(__DIR__.'/../../Forms/**/*DTO.php')
//    ;
//
//    $services->load($namespace.'\UseCase\\', __DIR__.'/../../UseCase')
//        ->exclude(__DIR__.'/../../UseCase/**/{*DTO.php,*Test.php}')
//    ;
//
//    $services->load($namespace.'\Security\\', __DIR__.'/../../Security');
};