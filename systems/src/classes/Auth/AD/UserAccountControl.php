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

// AD userAccountControl constants
// https://support.microsoft.com/en-gb/help/305144/
const SCRIPT = 1;
const ACCOUNTDISABLE = 2;
const HOMEDIR_REQUIRED = 8;
const LOCKOUT = 16;
const PASSWD_NOTREQD = 32;
const PASSWD_CANT_CHANGE = 64;
const ENCRYPTED_TEXT_PWD_ALLOWED = 128;
const TEMP_DUPLICATE_ACCOUNT = 256;
const NORMAL_ACCOUNT = 512;
const INTERDOMAIN_TRUST_ACCOUNT = 2048;
const WORKSTATION_TRUST_ACCOUNT = 4096;
const SERVER_TRUST_ACCOUNT = 8192;
const DONT_EXPIRE_PASSWORD = 65536;
const MNS_LOGON_ACCOUNT = 131072;
const SMARTCARD_REQUIRED = 262144;
const TRUSTED_FOR_DELEGATION = 524288;
const NOT_DELEGATED = 1048576;
const USE_DES_KEY_ONLY = 2097152;
const DONT_REQ_PREAUTH = 4194304;
const PASSWORD_EXPIRED = 8388608;
const TRUSTED_TO_AUTH_FOR_DELEGATION = 16777216;
const PARTIAL_SECRETS_ACCOUNT = 67108864;


class UserAccountControl
{
    public static function isActive($useraccountcontrol)
    {
        if (($useraccountcontrol & ACCOUNTDISABLE) == ACCOUNTDISABLE) {
            return false;
        }
        return true;
    }

    public static function hasPasswordExpired($useraccountcontrol)
    {
        if (($useraccountcontrol & PASSWORD_EXPIRED) == PASSWORD_EXPIRED) {
            return true;
        }
        return false;
    }
}
