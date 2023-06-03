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

use BaksDev\Core\Type\Locale\Locale;
use BaksDev\DeliveryTransport\Entity\DeliveryAuto\Event\DeliveryAutoEventInterface;
use BaksDev\DeliveryTransport\Type\DeliveryAuto\Event\DeliveryTransportEventUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryTransportEvent */
final class DeliveryTransportDTO implements DeliveryAutoEventInterface
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

    public function __construct()
    {
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
            $DeliveryAutoTransDTO = new Trans\DeliveryTransportTransDTO;
            $DeliveryAutoTransDTO->setLocal($locale);
            $this->addTranslate($DeliveryAutoTransDTO);
        }

        return $this->translate;
    }

    public function addTranslate(Trans\DeliveryTransportTransDTO $trans): void
    {
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
}
