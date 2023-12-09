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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Transport\NewEdit;

use BaksDev\Contacts\Region\Type\Call\Const\ContactsRegionCallConst;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\DeliveryTransport\Entity\Transport\Event\DeliveryTransportEventInterface;
use BaksDev\DeliveryTransport\Type\Transport\Event\DeliveryTransportEventUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Users\User\Entity\User;
use BaksDev\Users\User\Type\Id\UserUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryTransportEvent */
final class DeliveryTransportDTO implements DeliveryTransportEventInterface
{
    /**
     * Идентификатор события.
     */
    #[Assert\Uuid]
    private ?DeliveryTransportEventUid $id = null;

    /**
     * Регистрационный номер
     */
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 10)]
    private string $number;

    /**
     * Идентификатор профиля, за которым закреплен транспорт (Константа склада)
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private ?UserProfileUid $profile = null;

    /**
     * Флаг активности транспорта.
     */
    private bool $active = true;

    /**
     * Перевод.
     */
    #[Assert\Valid]
    private ArrayCollection $translate;

    /**
     * Параметры автомобиля.
     */
    #[Assert\Valid]
    private Parameter\DeliveryTransportParameterDTO $parameter;

    /**
     * Регион обслуживания.
     */
    #[Assert\Valid]
    private Region\DeliveryTransportRegionDTO $region;


    /** Вспомогательные свойства */
    private  UserUid $usr;


    public function __construct(User|UserUid $usr)
    {
        $this->usr = $usr instanceof User ? $usr->getId() : $usr;

        $this->translate = new ArrayCollection();
        $this->parameter = new  Parameter\DeliveryTransportParameterDTO();
        $this->region = new  Region\DeliveryTransportRegionDTO();
    }

    public function getEvent(): ?DeliveryTransportEventUid
    {
        return $this->id;
    }

    /**
     * Регистрационный номер
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * Флаг активности.
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * Перевод.
     */
    public function setTranslate(ArrayCollection $trans): void
    {
        $this->translate = $trans;
    }

    public function getTranslate(): ArrayCollection
    {
        /* Вычисляем расхождение и добавляем неопределенные локали */
        foreach (Locale::diffLocale($this->translate) as $locale)
        {
            $DeliveryTransportTransDTO = new Trans\DeliveryTransportTransDTO;
            $DeliveryTransportTransDTO->setLocal($locale);
            $this->addTranslate($DeliveryTransportTransDTO);
        }

        return $this->translate;
    }

    public function addTranslate(Trans\DeliveryTransportTransDTO $trans): void
    {
        if(empty($trans->getLocal()->getLocalValue()))
        {
            return;
        }

        if (!$this->translate->contains($trans))
        {
            $this->translate->add($trans);
        }
    }

    public function removeTranslate(Trans\DeliveryTransportTransDTO $trans): void
    {
        $this->translate->removeElement($trans);
    }

    /**
     * Параметры автомобиля.
     */
    public function getParameter(): Parameter\DeliveryTransportParameterDTO
    {
        return $this->parameter;
    }

    public function setParameter(Parameter\DeliveryTransportParameterDTO $parameter): void
    {
        $this->parameter = $parameter;
    }

    /**
     * Регион обслуживания.
     */
    public function getRegion(): Region\DeliveryTransportRegionDTO
    {
        return $this->region;
    }

    public function setRegion(Region\DeliveryTransportRegionDTO $region): void
    {
        $this->region = $region;
    }

    /**
     * Profile
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }

    public function setProfile(?UserProfileUid $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    /**
     * Usr
     */
    public function getUsr(): UserUid
    {
        return $this->usr;
    }





//    public function getWarehouse(): ?ContactsRegionCallConst
//    {
//        return $this->warehouse;
//    }
//
//    public function setWarehouse(?ContactsRegionCallConst $warehouse):void
//    {
//        $this->warehouse = $warehouse;
//    }


//    /**
//     * Id
//     */
//    public function getId(): ?DeliveryTransportEventUid
//    {
//        return $this->id;
//    }

//    public function setId(?DeliveryTransportEventUid $id): self
//    {
//        $this->id = $id;
//        return $this;
//    }

}
