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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\Delete;


use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEventInterface;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryTransportEvent */
final class DeliveryTransportDeleteDTO implements DeliveryTransportEventInterface
{
    /** Идентификатор события */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly DeliveryTransportEventUid $id;

    #[Assert\Valid]
    private Modify\DeliveryTransportModifyDTO $modify;

    public function __construct()
    {
        $this->modify = new Modify\DeliveryTransportModifyDTO();
    }

    public function getEvent(): DeliveryTransportEventUid
    {
        return $this->id;
    }
    
    public function setId(DeliveryTransportEvent|DeliveryTransportEventUid $id): void
    {
        if (!(new ReflectionProperty(self::class, 'id'))->isInitialized($this))
        {
            $this->id = $id instanceof DeliveryTransportEvent ? $id->getId() : $id;
        }
    }


    public function getModify(): Modify\DeliveryTransportModifyDTO
    {
        return $this->modify;
    }
}
