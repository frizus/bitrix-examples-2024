<?php
namespace Frizus\Module\Rule;

use Frizus\Module\Rule\Base\Rule;

class EmptyStringRule extends Rule
{
    public function passes($attribute, $value, $keyExists)
    {
        return is_string($value) && ($value === '');
    }
}