<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\DeliveryTransport\Type\Transport\Id;

use App\Kernel;
use BaksDev\Core\Type\UidType\Uid;
use Symfony\Component\Uid\AbstractUid;

final class DeliveryTransportUid extends Uid
{
    public const string TEST = '0188a997-f5a5-7c49-bc0d-ab94fbe0f83d';

    public const string TYPE = 'delivery_auto';

    private mixed $attr;

    private mixed $option;

    private mixed $carrying;

    private mixed $size;

    public function __construct(
        AbstractUid|self|string|null $value = null,
        mixed $attr = null,
        mixed $option = null,
        mixed $size = null,
        mixed $carrying = null,
    )
    {
        parent::__construct($value);

        $this->attr = $attr;
        $this->option = $option;
        $this->carrying = $carrying;
        $this->size = $size;
    }

    public function getAttr(): mixed
    {
        return $this->attr;
    }

    public function getOption(): mixed
    {
        return $this->option;
    }

    /**
     * Грузоподъемность
     */
    public function getCarrying(): mixed
    {
        return $this->carrying;
    }

    /**
     * Объем кузова
     */
    public function getSize(): mixed
    {
        return $this->size;
    }
}
