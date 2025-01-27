<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\Error;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use DomainException;

final class ErrorProductStockHandler extends AbstractHandler
{

    public function handle(ErrorProductStockDTO $command): string|ProductStock
    {
        /** Валидация  $command */
        $this->validatorCollection->add($command);

        $this->main = new ProductStock();
        $this->event = new ProductStockEvent();

        try
        {
            $this->preUpdate($command, true);
        }
        catch(DomainException $errorUniqid)
        {
            return $errorUniqid->getMessage();
        }

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->flush();

        //        /* НЕ Отправляем сообщение в шину при ошибке */
        //        $this->messageDispatch->dispatch(
        //            message: new ProductStockMessage($this->main->getId(), $this->main->getEvent(), $command->getEvent()),
        //            transport: 'products-stocks',
        //        );

        return $this->main;
    }

    //    public function _handle(
    //        ErrorProductStockDTO $command,
    //    ): string|ProductStock
    //    {
    //        /* Валидация DTO */
    //        $errors = $this->validator->validate($command);
    //
    //        if(count($errors) > 0)
    //        {
    //            /** Ошибка валидации */
    //            $uniqid = uniqid('', false);
    //            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [self::class.':'.__LINE__]);
    //
    //            return $uniqid;
    //        }
    //
    //        if($command->getEvent())
    //        {
    //            $EventRepo = $this->entityManager->getRepository(ProductStockEvent::class)->find(
    //                $command->getEvent()
    //            );
    //
    //            if($EventRepo === null)
    //            {
    //                $uniqid = uniqid('', false);
    //                $errorsString = sprintf(
    //                    'Not found %s by id: %s',
    //                    ProductStockEvent::class,
    //                    $command->getEvent()
    //                );
    //                $this->logger->error($uniqid.': '.$errorsString);
    //
    //                return $uniqid;
    //            }
    //
    //
    //            $EventRepo->setEntity($command);
    //            $EventRepo->setEntityManager($this->entityManager);
    //            $Event = $EventRepo->cloneEntity();
    ////            $this->entityManager->clear();
    ////            $this->entityManager->persist($Event);
    //        }
    //        else
    //        {
    //            $uniqid = uniqid('', false);
    //            $errorsString = sprintf(
    //                'Not found %s by id: %s',
    //                ProductStockEvent::class,
    //                $command->getEvent()
    //            );
    //            $this->logger->error($uniqid.': '.$errorsString);
    //
    //            return $uniqid;
    //        }
    //
    //
    //
    //        /* @var ProductStock $Main */
    //        if($Event->getMain())
    //        {
    //            $Main = $this->entityManager->getRepository(ProductStock::class)
    //                ->findOneBy(['event' => $command->getEvent()]);
    //
    //            if(empty($Main))
    //            {
    //                $uniqid = uniqid('', false);
    //                $errorsString = sprintf(
    //                    'Not found %s by event: %s',
    //                    ProductStock::class,
    //                    $command->getEvent()
    //                );
    //                $this->logger->error($uniqid.': '.$errorsString);
    //
    //                return $uniqid;
    //            }
    //        }
    //        else
    //        {
    //            $Main = new ProductStock();
    //            $this->entityManager->persist($Main);
    //            $Event->setMain($Main);
    //        }
    //
    //        /* присваиваем событие корню */
    //        $Main->setEvent($Event);
    //
    //        /**
    //         * Валидация Event
    //         */
    //
    //        $errors = $this->validator->validate($Event);
    //
    //        if(count($errors) > 0)
    //        {
    //            /** Ошибка валидации */
    //            $uniqid = uniqid('', false);
    //            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [self::class.':'.__LINE__]);
    //
    //            return $uniqid;
    //        }
    //
    //
    //
    //        /**
    //         * Валидация Main
    //         */
    //        $errors = $this->validator->validate($Main);
    //
    //        if(count($errors) > 0)
    //        {
    //            /** Ошибка валидации */
    //            $uniqid = uniqid('', false);
    //            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [self::class.':'.__LINE__]);
    //
    //            return $uniqid;
    //        }
    //
    //        $this->entityManager->flush();
    //
    //        /* Отправляем событие в шину  */
    //        //        $this->messageDispatch->dispatch(
    //        //            message: new ProductStockMessage($Main->getId(), $Main->getEvent(), $command->getEvent()),
    //        //            transport: 'products_stocks'
    //        //        );
    //
    //        return $Main;
    //    }
}
