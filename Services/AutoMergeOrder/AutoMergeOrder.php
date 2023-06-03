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

namespace BaksDev\DeliveryTransport\Services\AutoMergeOrder;

use BaksDev\Orders\Order\Type\Id\OrderUid;

final class AutoMergeOrder implements AutoMergeOrderInterface
{





    /** Метод пробует добавить заказ к доступному транспорту и возвращает его идентификатор */
    public function mergeOrder(OrderUid $order) : bool
    {
        return false;
    }





    public function testMerge(): void
    {
        /* Создать массив товаров, где каждый элемент будет содержать информацию о размере товара и его объеме. */
        $products = [
            ['name' => 'Товар 1', 'width' => 50, 'height' => 20, 'length' => 30, 'total' => 3],
            ['name' => 'Товар 2', 'width' => 40, 'height' => 25, 'length' => 20, 'total' => 1],
            ['name' => 'Товар 3', 'width' => 60, 'height' => 30, 'length' => 35, 'total' => 2],
            ['name' => 'Товар 4', 'width' => 60, 'height' => 30, 'length' => 35, 'total' => 4],
            ['name' => 'Товар 5', 'width' => 60, 'height' => 30, 'length' => 35, 'total' => 8],
            ['name' => 'Товар 6', 'width' => 60, 'height' => 30, 'length' => 35, 'total' => 5],
            ['name' => 'Товар 7', 'width' => 60, 'height' => 30, 'length' => 35, 'total' => 1],
        ];

        // Определить размеры кузова автомобиля и его объем.
        $truck = ['width' => 50, 'height' => 50, 'length' => 60];
        $truckVolume = $truck['width'] * $truck['height'] * $truck['length']; // 500 000

        $loadedVolume = 0;
        foreach ($products as $product)
        {
            // вычисляем объем товара в kub.m
            $goodVolume = $product['width'] * $product['height'] * $product['length'];

            // проверяем, что товар помещается в кузов
            if ($truckVolume >= $goodVolume)
            {
                $truckVolume -= $goodVolume;
                echo 'Загружено: '.$product['name'].'<br>';
            }
        }

        echo 'Оставшийся свободный объем кузова: '.$truckVolume.' куб.м';
    }
}
