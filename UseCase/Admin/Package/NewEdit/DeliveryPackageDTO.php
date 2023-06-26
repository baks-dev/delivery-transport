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

namespace BaksDev\DeliveryTransport\UseCase\Admin\Package\NewEdit;

use BaksDev\DeliveryTransport\Entity\Package\Event\DeliveryPackageEventInterface;
use BaksDev\DeliveryTransport\Type\Package\Event\DeliveryPackageEventUid;
use BaksDev\DeliveryTransport\Type\Package\Status\DeliveryPackageStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see DeliveryPackageEvent */
final class DeliveryPackageDTO implements DeliveryPackageEventInterface
{
    /** Идентификатор события */
    #[Assert\Uuid]
    private ?DeliveryPackageEventUid $id = null;

//    /** Заказы в поставке */
//    private ArrayCollection $ord;
//
//    /** Заявки на перемещение */
//    private ArrayCollection $move;


    /** Складские заявки */
    private ArrayCollection $stock;

    /** Статус поставки */
    #[Assert\NotBlank]
    private DeliveryPackageStatus $status;

    public function __construct()
    {
        //$this->ord = new ArrayCollection();
        $this->stock = new ArrayCollection();
        $this->status = new DeliveryPackageStatus(new DeliveryPackageStatus\DeliveryPackageStatusNew());
    }

    public function getEvent(): ?DeliveryPackageEventUid
    {
        return $this->id;
    }

    public function setId(?DeliveryPackageEventUid $id): void
    {
        $this->id = $id;
    }

//    /**
//     * Заказы в поставке.
//     */
//    public function getOrd(): ArrayCollection
//    {
//        return $this->ord;
//    }
//
//    public function setOrd(ArrayCollection $orders): void
//    {
//        $this->ord = $orders;
//    }
//
//    public function addOrd(Order\DeliveryPackageOrderDTO $order): void
//    {
//        $this->ord->add($order);
//    }
//
//    public function removeOrd(Order\DeliveryPackageOrderDTO $order): void
//    {
//        $this->ord->removeElement($order);
//    }



    /**
     * Заявки на перемещение
     */
    public function getStock(): ArrayCollection
    {
        return $this->stock;
    }

    public function setStock(ArrayCollection $stock): void
    {
        $this->stock = $stock;
    }

    public function addStock(Stocks\DeliveryPackageStocksDTO $stock): void
    {
        $this->stock->add($stock);
    }

    public function removeStock(Stocks\DeliveryPackageStocksDTO $stock): void
    {
        $this->stock->removeElement($stock);
    }

    
    /**
     * Статус поставки.
     */
    public function getStatus(): DeliveryPackageStatus
    {
        return $this->status;
    }

    public function setStatus(DeliveryPackageStatus $status): void
    {
        $this->status = $status;
    }
}
