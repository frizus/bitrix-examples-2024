<?
namespace Frizus\Module\Helper;

use Bitrix\Iblock\PropertyTable;

class PropertyValues
{
    public static function normalize($values, $PROPERTY_CODE, $iblockId)
    {
        if ($PROPERTY_CODE !== false) {
            $values = [
                $PROPERTY_CODE => $values,
            ];
        }

        $ids = [];

        foreach ($values as $propertyCode => &$value) {
            if ((int)$propertyCode > 0) {
                $ids[] = $propertyCode;
            }

            if (!is_array($value)) {
                $value = [
                    [
                        'VALUE' => $value,
                        'DESCRIPTION' => '',
                    ]
                ];
                continue;
            }

            if (is_array($value)) {
                foreach ($value as &$singleValue) {
                    if (!is_array($singleValue)) {
                        $singleValue = [
                            'VALUE' => $singleValue,
                            'DESCRIPTION' => '',
                        ];
                    }
                }
                unset($singleValue);
            }

            if ((count($value) === 2) &&
                array_key_exists('VALUE', $value) &&
                array_key_exists('DESCRIPTION', $value)
            ) {
                $value = [$value];
                continue;
            }

            foreach ($value as $cursor => $singleValue) {
                if (!(
                    (
                        !is_array($singleValue['VALUE']) &&
                        strval($singleValue['VALUE']) !== ''
                    ) ||
                    (
                        is_array($singleValue['VALUE']) &&
                        !empty($singleValue['VALUE'])
                    )
                )) {
                    unset($value[$cursor]);
                    continue;
                }
                
                if (!is_array($singleValue['VALUE']) && 
                    !is_string($singleValue['VALUE'])
                ) {
                    $value[$cursor]['VALUE'] = strval($singleValue['VALUE']);
                }

                if (!is_array($singleValue['DESCRIPTION']) &&
                    !is_string($singleValue['DESCRIPTION'])
                ) {
                    $value[$cursor]['DESCRIPTION'] = strval($value[$cursor]['DESCRIPTION']);
                }
            }
        }
        unset($value);

        if (!empty($ids)) {
            $codes = self::getPropertyCodes($ids, $iblockId);
            foreach ($codes as $id => $code) {
                if (array_key_exists($id, $values)) {
                    $values[$code] = $values[$id];
                    unset($values[$id]);
                }
            }
        }

        return $values;
    }
    
    public static function normalizeEx($values, $iblockId)
    {
        $ids = [];

        foreach ($values as $propertyCode => &$value) {
            if ((int)$propertyCode > 0) {
                $ids[] = $propertyCode;
            }

            if (!is_array($value)) {
                $value = [['VALUE' => $value, 'DESCRIPTION' => '']];
            } else {
                $ar = array_keys($value);
                if ((count($ar) === 2) &&
                    ($ar[0] === 'VALUE') &&
                    ($ar[1] === 'DESCRIPTION')
                ) {
                    $value = [['VALUE' => $value['VALUE'], 'DESCRIPTION' => $value['DESCRIPTION']]];
                    continue;
                }

                if ((count($ar) === 1) &&
                    ($ar[0] === 'VALUE')
                ) {
                    $value = [['VALUE' => $value['VALUE'], 'DESCRIPTION' => $value['DESCRIPTION']]];
                    continue;
                }

                $newValue = [];
                foreach ($value as $id => $val) {
                    if (!is_array($val)) {
                        $newValue[] = ['VALUE' => $val, 'DESCRIPTION' => ''];
                    } else {
                        $ar = array_keys($val);
                        if (($ar[0]==="VALUE") &&
                            ($ar[1]==="DESCRIPTION")
                        ) {
                            $newValue[] = ["VALUE" => $val["VALUE"], "DESCRIPTION" => $val["DESCRIPTION"]];
                        } elseif(count($ar)===1 && $ar[0]==="VALUE") {
                            $newValue[] = ["VALUE" => $val["VALUE"], "DESCRIPTION"=>""];
                        }
                    }
                }
                $value = $newValue;
                unset($newValue);
            }

            foreach ($value as $cursor => $singleValue) {
                if (!(
                    (
                        !is_array($singleValue['VALUE']) &&
                        strval($singleValue['VALUE']) !== ''
                    ) ||
                    (
                        is_array($singleValue['VALUE']) &&
                        !empty($singleValue['VALUE'])
                    )
                )) {
                    unset($value[$cursor]);
                    continue;
                }

                if (!is_array($singleValue['VALUE']) &&
                    !is_string($singleValue['VALUE'])
                ) {
                    $value[$cursor]['VALUE'] = strval($singleValue['VALUE']);
                }

                if (!is_array($singleValue['DESCRIPTION']) &&
                    !is_string($singleValue['DESCRIPTION'])
                ) {
                    $value[$cursor]['DESCRIPTION'] = strval($value[$cursor]['DESCRIPTION']);
                }
            }
        }
        unset($value);

        if (!empty($ids)) {
            $codes = self::getPropertyCodes($ids, $iblockId);
            foreach ($codes as $id => $code) {
                if (array_key_exists($id, $values)) {
                    $values[$code] = $values[$id];
                    unset($values[$id]);
                }
            }
        }

        return $values;
    }

    protected static function getPropertyCodes($ids, $iblockId)
    {
        static $codes = [];

        $resultCodes = [];

        if (array_key_exists($iblockId, $codes)) {
            foreach ($ids as $cursor => $id) {
                if (array_key_exists($id, $codes[$iblockId])) {
                    $resultCodes[$id] = $codes[$iblockId][$id];
                    unset($ids[$cursor]);
                }
            }
        }

        if (!empty($ids)) {
            $result = PropertyTable::getList([
                'filter' => [
                    '=IBLOCK_ID' => $iblockId,
                    '@ID' => $ids,
                ],
                'select' => [
                    'ID',
                    'CODE',
                ]
            ]);

            while ($row = $result->fetch()) {
                $resultCodes[$row['ID']] = $row['CODE'];
                $codes[$iblockId][$row['ID']] = $row['CODE'];
            }
        }

        return $resultCodes;
    }
}
