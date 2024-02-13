<?php
namespace Override;
// не используется. пример переопределенного класса
// @see \Frizus\Module\Handlers\OrderPropsValueBypassLengthValidation::OrderPropsValueOnBeforeUpdate

use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/lib/internals/orderprops.php');

class OverrideOrderPropsValueTable extends \Bitrix\Sale\Internals\OrderPropsValueTable
{
    public static function getMap()
    {
        $map = parent::getMap();

        if (key_exists('VALUE', $map)) {
            $map['VALUE']['validation'] = [static::class, 'overridenGetValueValidators'];
        }

        return $map;
    }

    /**
     * @see \Bitrix\Sale\Internals\OrderPropsTable::getValueValidators
     */
    public static function overridenGetValueValidators()
    {
        return [
            [static::class, 'overridenValidateValue']
        ];
    }

    /**
     * @see \Bitrix\Sale\Internals\OrderPropsTable::validateValue
     */
    public static function overridenValidateValue($value, $primary, array $row, $field)
    {
        $maxlength = 65535;

        $valueForSave = \Bitrix\Sale\Internals\OrderPropsTable::modifyValueForSave($value, $row);
        $length = isset($valueForSave) ? mb_strlen($valueForSave) : 0;

        return $length > $maxlength
            ? Loc::getMessage('SALE_ORDER_PROPS_DEFAULT_ERROR', array('#PROPERTY_NAME#'=> $row['NAME'],'#FIELD_LENGTH#' => $length, '#MAX_LENGTH#' => $maxlength))
            : true;
    }
}