<?php

namespace Frizus;

use Frizus\Cart\OrderHelper;

class ReCaptcha
{
    public static function isEnabled()
    {
        return Domain::isProductionDomainWithAltProductionDomains();
    }

    public static function isDisabled()
    {
        return !static::isEnabled();
    }
}