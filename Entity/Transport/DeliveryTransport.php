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

namespace BaksDev\DeliveryTransport\Entity\Transport;

use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEvent;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/* DeliveryTransport */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_transport')]
class DeliveryTransport
{
    public const TABLE = 'delivery_transport';

    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: DeliveryTransportUid::TYPE)]
    private DeliveryTransportUid $id;


    /** ID События */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: DeliveryTransportEventUid::TYPE, unique: true)]
    private DeliveryTransportEventUid $event;

    public function __construct()
    {
        $this->id = new DeliveryTransportUid();
    }

    public function getId(): DeliveryTransportUid
    {
        return $this->id;
    }

    public function getEvent(): DeliveryTransportEventUid
    {
        return $this->event;
    }

    public function setEvent(DeliveryTransportEventUid|DeliveryTransportEvent $event): void
    {
        $this->event = $event instanceof DeliveryTransportEvent ? $event->getId() : $event;
    }
}