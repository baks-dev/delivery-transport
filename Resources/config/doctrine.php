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


use BaksDev\DeliveryTransport\Type\Package\Event\DeliveryPackageEventType;
use BaksDev\DeliveryTransport\Type\Package\Event\DeliveryPackageEventUid;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageType;
use BaksDev\DeliveryTransport\Type\Package\Id\DeliveryPackageUid;
use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatus;
use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatusType;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\Kilogram;
use BaksDev\DeliveryTransport\Type\ProductParameter\Weight\Kilogram\KilogramType;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventType;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportType;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use Symfony\Config\DoctrineConfig;

return static function (ContainerConfigurator $container, DoctrineConfig $doctrine): void {

    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $doctrine->dbal()->type(DeliveryTransportUid::TYPE)->class(DeliveryTransportType::class);
    $doctrine->dbal()->type(DeliveryTransportEventUid::TYPE)->class(DeliveryTransportEventType::class);

    $doctrine->dbal()->type(DeliveryPackageUid::TYPE)->class(DeliveryPackageType::class);
    $services->set(DeliveryPackageUid::class)->class(DeliveryPackageUid::class); // #[ParamConverter(['package'])] DeliveryPackageUid $package,


    $doctrine->dbal()->type(DeliveryPackageEventUid::TYPE)->class(DeliveryPackageEventType::class);

    $doctrine->dbal()->type(DeliveryPackageStatus::TYPE)->class(DeliveryPackageStatusType::class);
    
    $doctrine->dbal()->type(Kilogram::TYPE)->class(KilogramType::class);

    $emDefault = $doctrine->orm()->entityManager('default');

    $emDefault->autoMapping(true);
    $emDefault->mapping('DeliveryTransport')
        ->type('attribute')
        ->dir(__DIR__.'/../../Entity')
        ->isBundle(false)
        ->prefix('BaksDev\DeliveryTransport\Entity')
        ->alias('DeliveryTransport');
};
