<?php

/*
 * This file is a part of Riskpoint Framework Software which is released under
 * MIT Open-Source license
 *
 * Riskpoint Framework Software License - MIT License
 *
 * Copyright (C) 2008 - 2015 Riskpoint Limited
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
namespace Riskpoint\Auth\AD;

class FullName
{
    private static $options = array(
        'DisplayName',
        'CommonName',
        'Name',
        'FirstName LastName',
        'LastName, FirstName'
    );

    public static function getOptions() {
        return self::$options;
    }

    public static function get(\Adldap\Models\User $userObject, $style = 'DisplayName')
    {
        if ($style == 'Name') {
            return $userObject->getName();
        }
        if ($style == 'DisplayName') {
            return $userObject->getDisplayName();
        }
        if ($style == 'CommonName') {
            return $userObject->getCommonName();
        }
        if ($style == 'FirstName LastName') {
            return $userObject->getFirstName() . ' ' . $userObject->getLastName();
        }
        if ($style == 'LastName, FirstName') {
            return $userObject->getLastName() . ', ' . $userObject->getFirstName();
        }
        throw new \Exception("Unknown format specified for user Full Name field.");
    }
}
