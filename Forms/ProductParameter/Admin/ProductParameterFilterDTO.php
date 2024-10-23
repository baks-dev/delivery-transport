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

namespace BaksDev\DeliveryTransport\Forms\ProductParameter\Admin;

use BaksDev\DeliveryTransport\Forms\ProductParameter\ProductParameterFilterInterface;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use Symfony\Component\HttpFoundation\Request;

final class ProductParameterFilterDTO implements ProductParameterFilterInterface
{
    public const category = 'XHJRudPaDx';

    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    /** Категория */
    private ?CategoryProductUid $category = null;


    public function setCategory(CategoryProductUid|string|null $category): void
    {
        if($category === null)
        {
            $this->request->getSession()->remove(self::category);
        }

        if(is_string($category))
        {
            $category = new CategoryProductUid($category);
        }

        $this->category = $category;
    }


    public function getCategory(): ?CategoryProductUid
    {
        return $this->category ?: $this->request->getSession()->get(self::category);
    }


}

