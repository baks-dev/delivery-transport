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

namespace BaksDev\DeliveryTransport\Entity\Transport\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\DeliveryTransport\Entity\Transport\DeliveryTransport;
use BaksDev\DeliveryTransport\Entity\Transport\Modify\DeliveryTransportModify;
use BaksDev\DeliveryTransport\Entity\Transport\Parameter\DeliveryTransportParameter;
use BaksDev\DeliveryTransport\Entity\Transport\Region\DeliveryTransportRegion;
use BaksDev\DeliveryTransport\Entity\Transport\Trans\DeliveryTransportTrans;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\DeliveryTransport\Type\Transport\Id\DeliveryTransportUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* DeliveryTransportRegion */

#[ORM\Entity]
#[ORM\Table(name: 'delivery_transport_event')]
class DeliveryTransportEvent extends EntityEvent
{
    public const TABLE = 'delivery_transport_event';

    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: DeliveryTransportEventUid::TYPE)]
    private DeliveryTransportEventUid $id;

    /** ID DeliveryTransport */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: DeliveryTransportUid::TYPE, nullable: false)]
    private ?DeliveryTransportUid $main = null;

    /** Регистрационный номер */
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 10)]
    #[ORM\Column(type: Types::STRING)]
    private string $number;

    /** Флаг активности */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $active = true;

    /** Модификатор */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: DeliveryTransportModify::class, cascade: ['all'])]
    private DeliveryTransportModify $modify;

    /** Перевод */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: DeliveryTransportTrans::class, cascade: ['all'])]
    private Collection $translate;

    /** Параметры автомобиля */
    #[Assert\Valid]
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: DeliveryTransportParameter::class, cascade: ['all'])]
    private DeliveryTransportParameter $parameter;
    
    /** Регион обслуживания */
    #[Assert\Valid]
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: DeliveryTransportRegion::class, cascade: ['all'])]
    private DeliveryTransportRegion $region;


    public function __construct()
    {
        $this->id = new DeliveryTransportEventUid();
        $this->modify = new DeliveryTransportModify($this);
    }

    public function __clone()
    {
        $this->id = new DeliveryTransportEventUid();
    }

    public function __toString(): string
    {
        return (string)$this->id;
    }

    public function getId(): DeliveryTransportEventUid
    {
        return $this->id;
    }

    public function setMain(DeliveryTransportUid|DeliveryTransport $main): void
    {
        $this->main = $main instanceof DeliveryTransport ? $main->getId() : $main;
    }


    public function getMain(): ?DeliveryTransportUid
    {
        return $this->main;
    }

    public function getDto($dto): mixed
    {
        if ($dto instanceof DeliveryTransportEventInterface) {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if ($dto instanceof DeliveryTransportEventInterface) {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }



	public function getNameByLocale(Locale $locale) : ?string
	{
		$name = null;
		
		/** @var ${MainClass}Trans $trans */
		foreach($this->translate as $trans)
		{
			if($name = $trans->name($locale))
			{
				break;
			}
		}
		
		return $name;
	}
}