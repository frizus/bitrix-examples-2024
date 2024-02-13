<?php
namespace Frizus\IBlock;

use Bitrix\Catalog\MeasureRatioTable;
use Bitrix\Catalog\MeasureTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\Component\Tools;
use Frizus\Traits\FilterTrait;
use Frizus\Helpers\Arr;

class ElementQuery
{
    use FilterTrait;

    public static function getActiveAvailableProducts($ids)
    {
        return static::getProducts($ids, true, true);
    }

    public static function getActiveProducts($ids)
    {
        return static::getProducts($ids, true);
    }

    public static function getProducts($ids, $active = null, $catalogAvailable = null)
    {
        return getAndCache(function($ids, $active, $catalogAvailable) {
            $filter = static::activeAvailableFilter($active, $catalogAvailable);
            return static::queryProducts($ids, $filter);
        }, $ids, null, [$active, $catalogAvailable], getAlsoCacheAs($active, $catalogAvailable));
    }

    public static function getActiveElements($ids)
    {
        return static::getElements($ids, true);
    }

    public static function getElements($ids, $active = null)
    {
        return getAndCache(function($ids, $active) {
            $filter = static::activeFilter($active);
            return static::queryElements($ids, $filter);
        }, $ids, null, [$active], getAlsoCacheAs($active));
    }

    protected static function queryProducts($ids, $filter = null)
    {
        if (!($rows = static::queryElements(
            $ids,
            $filter,
            [
                'TYPE',
                'MEASURE',
                'CAN_BUY_ZERO',
                'QUANTITY_TRACE',
                'QUANTITY',
            ]
        ))) {
            return $rows;
        }

        $groups = &Arr::groupBy($rows, 'MEASURE');

        /** @see @see \Bitrix\Iblock\Component\Base::loadMeasures */
        if (($measures = Arr::filterOutEmpty(array_keys($groups)))) {
            $result = \CCatalogMeasure::getList(
                [],
                ['@ID' => $measures],
                false,
                false,
                ['ID', 'SYMBOL_RUS']
            );
            /*$result = MeasureTable::getList([
                'filter' => [
                    '@ID' => $measures,
                ],
            ]);*/

            while ($row = $result->fetch()) {
                foreach ($groups[$row['ID']] as &$row2) {
                    $row2['MEASURE_NAME'] = $row['SYMBOL_RUS'];
                    $row2['ITEM_MEASURE'] = $row;
                }
                unset($row2);
            }
            unset($row);
        }

        $result = MeasureRatioTable::getList([
            'filter' => [
                '@PRODUCT_ID' => array_keys($rows),
            ]
        ]);

        /**
         * @see \Bitrix\Iblock\Component\Base::loadMeasureRatios
         * @TODO \Bitrix\Iblock\Component\Base::searchItemSelectedRatioId
         */
        while ($row = $result->fetch()) {
            $ratio = max((float)$row['RATIO'], (int)$row['RATIO']);
            if ($ratio > CATALOG_VALUE_EPSILON) {
                $row2 = &$rows[$row['PRODUCT_ID']];
                $row['RATIO'] = $ratio;
                $row2['MEASURE_RATIO'] = $row['RATIO'];
                $row2['ITEM_MEASURE_RATIO'] = $row;
            }
        }
        unset($row);
        
        return $rows;
    }

    /**
     * @see \Bitrix\Iblock\Component\Element::modifyDisplayProperties
     */
    protected static function queryElements($ids, $filter = null, $select = null)
    {
        if (!$ids) {
            return [];
        }

        $result = \CIBlockElement::GetList(
            [],
            array_merge(
                $filter ?: [],
                [
                    'ID' => array_values($ids),
                ]
            ),
            false,
            false,
            array_merge(
                [
                    'ID',
                    'ACTIVE',
                    'NAME',
                    'XML_ID',
                    'IBLOCK_ID',
                    'IBLOCK_SECTION_ID',
                    'DETAIL_PICTURE',
                    'PREVIEW_PICTURE',
                    'DETAIL_PAGE_URL',
                ],
                $select ?: []
            )
        );

        $rows = [];
        while ($row = $result->GetNextElement(true, false)) {
            $rows[$row->fields['ID']] = new ElementData($row);
        }

        return $rows;
    }
}