<?php

namespace Frizus\Cart;

use Bitrix\Sale\Discount\Formatter;

class DiscountApplyEmulator
{
    public function __construct($currentPrice, $currency)
    {
        $this->currentPrice = $currentPrice;
        $this->currency = $currency;
    }

    public function emulateApplyDiscount($row, $dontApplyIfLessThanZero = false)
    {
        $type = (int)$row['TYPE'];
        $discountPrice = null;

        if ($type === Formatter::TYPE_SIMPLE) {
            return false;
            //TODO что за тип TYPE_SIMPLE
            //$discountPrice = toCur((float)$row['VALUE'], $row['VALUE_UNIT'], $currency);
        } elseif (in_array(
            $type,
            [
                Formatter::TYPE_VALUE,
                Formatter::TYPE_LIMIT_VALUE,
                Formatter::TYPE_MAX_BOUND,
            ],
            true
        )) {
            $resultValue = (float)$row['RESULT_VALUE'];
            if ($row['VALUE_ACTION'] === Formatter::VALUE_ACTION_EXTRA) {
                $resultValue = -$resultValue;
            }
            $discountPrice = toCur($resultValue, $row['RESULT_UNIT'], $this->currency);
        } elseif ($type === Formatter::TYPE_FIXED) {
            $discountPrice = toCur($this->currentPrice - $row['VALUE'], $row['VALUE_UNIT'], $this->currency);
        } elseif ($type === Formatter::TYPE_SIMPLE_GIFT) {
            $discountPrice = $this->currentPrice;
        } else {
            return false;
        }

        /*if (isset($row['LIMIT_TYPE']) &&
            isset($row['LIMIT_VALUE']) &&
            isset($row['LIMIT_UNIT']) &&
            ($row['LIMIT_TYPE'] === Formatter::LIMIT_MAX) &&
            ($discountPrice > ($limitValue = toCur($row['LIMIT_VALUE'], $row['LIMIT_UNIT'], $currency)))
        ) {
            $discountPrice = $limitValue;
        }*/

        $currentPrice = $this->currentPrice - $discountPrice;
        if ($dontApplyIfLessThanZero) {
            if ($currentPrice < 0.0) {
                $discountPrice = null;
            }
        }

        if (!is_null($discountPrice)) {
            $this->currentPrice = $currentPrice;
        }

        return $discountPrice;
    }

    public function emulateApplyRawValue($discountPrice, $dontApplyIfLessThanZero = false)
    {
        $currentPrice = $this->currentPrice - $discountPrice;
        if ($dontApplyIfLessThanZero) {
            if ($currentPrice < 0.0) {
                $discountPrice = null;
            }
        }

        if (!is_null($discountPrice)) {
            $this->currentPrice = $currentPrice;
        }

        return $discountPrice;
    }

    public function getCurrentPrice()
    {
        return $this->currentPrice;
    }
}