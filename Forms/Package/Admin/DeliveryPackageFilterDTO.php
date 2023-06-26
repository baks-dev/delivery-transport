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

namespace BaksDev\DeliveryTransport\Forms\Package\Admin;

use BaksDev\DeliveryTransport\Forms\Package\DeliveryPackageFilterInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

final class DeliveryPackageFilterDTO implements DeliveryPackageFilterInterface
{
    public const date = 'qoXfTWWulu';

    private Request $request;

    private ?UserProfileUid $profile;

    public function __construct(Request $request, ?UserProfileUid $profile)
    {
        $this->request = $request;
        $this->profile = $profile;
    }

    /** Дата */
    private ?DateTimeImmutable $date = null;

    /**
     * Date.
     */
    public function getDate(): ?DateTimeImmutable
    {

        $sessionDate = $this->request->getSession()->get(self::date) ?: null;

        return $this->date ?: $sessionDate ;
    }

    public function setDate(?DateTimeImmutable $date): void
    {
        if ($date === null)
        {
            $this->request->getSession()->remove(self::date);
        }

        $this->date = $date;
    }

    /**
     * Profile.
     */
    public function getProfile(): ?UserProfileUid
    {
        return $this->profile;
    }
}
