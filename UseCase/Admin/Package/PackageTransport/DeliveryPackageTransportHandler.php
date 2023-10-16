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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\PackageTransport;

use BaksDev\DeliveryTransport\Entity\Package\DeliveryPackageTransport;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DeliveryPackageTransportHandler
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger,
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /** @see DeliveryPackageTransport */
    public function handle(
        DeliveryPackageTransportDTO $command,
        //?UploadedFile $cover = null
    ): string|DeliveryPackageTransport {
        /**
         *  Валидация DeliveryPackageTransportDTO.
         */
        $errors = $this->validator->validate($command);

        if (count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);

            return $uniqid;
        }
        
        $this->entityManager->clear();

        $DeliveryPackageTransport = $this->entityManager->getRepository(DeliveryPackageTransport::class)
            ->findOneBy(
                [
                    'package' => $command->getPackage(),
                    'transport' => $command->getTransport(),
                    'date' => $command->getDate()
                ]
            );

        if (empty($DeliveryPackageTransport))
        {
            $DeliveryPackageTransport = new DeliveryPackageTransport(
                $command->getPackage(),
                $command->getTransport(),
                $command->getDate()
            );

            $this->entityManager->persist($DeliveryPackageTransport);
        }

        $DeliveryPackageTransport->setEntity($command);

        /**
         * Валидация DeliveryPackageTransport.
         */
        $errors = $this->validator->validate($DeliveryPackageTransport);

        if (count($errors) > 0)
        {
            /** Ошибка валидации */
            $uniqid = uniqid('', false);
            $this->logger->error(sprintf('%s: %s', $uniqid, $errors), [__FILE__.':'.__LINE__]);

            return $uniqid;
        }

        $this->entityManager->flush();

        return $DeliveryPackageTransport;
    }
}
