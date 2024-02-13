<?php

namespace Frizus\IBlock\Traits;

use Frizus\Helpers\Arr;

trait AvailableFieldAlgorithm
{
    public static function firstAvailableFieldAlgorithm($key, $row, $checkIfValueIsNullOrEmptyString)
    {
        $value = Arr::get($key, $row);

        if (is_null($checkIfValueIsNullOrEmptyString) ||
            (
                $checkIfValueIsNullOrEmptyString &&
                (
                    !is_null($value) &&
                    ($value !== '')
                )
            ) ||
            (
                !$checkIfValueIsNullOrEmptyString &&
                $value
            )
        ) {
            return [true, $value];
        }

        return [false, $value];
    }
}