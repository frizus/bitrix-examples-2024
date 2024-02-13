<?php

namespace Frizus\Catalog;

use Bitrix\Sale\StoreProductTable;
use Frizus\IBlock\ElementQuery;
use Frizus\IBlock\Product;

class ProductStore
{
    public static function getAvailableAmount($ids, $active = null, $isIssuingCenter = null)
    {
        static $haveQuantityReserved;

        if (!isset($haveQuantityReserved)) {
            $haveQuantityReserved = key_exists('QUANTITY_RESERVED', StoreProductTable::getMap());
        }

        return getAndCache(function($ids, $active, $isIssuingCenter) use($haveQuantityReserved) {
            if (!($ids = Product::filterByActiveAvailable($ids))) {
                return;
            }

            $filter = [
                '@PRODUCT_ID' => $ids,
                '>AMOUNT' => 0,
            ];
            if (!is_null($active)) {
                $filter['=STORE.ACTIVE'] = to_bool($active);
            }
            if (!is_null($isIssuingCenter)) {
                $filter['=STORE.ISSUING_CENTER'] = to_bool($isIssuingCenter);
            }

            $parameters = [
                'filter' => $filter,
            ];

            if (!$haveQuantityReserved) {
                $parameters += [
                    'runtime' => [
                        'QUANTITY_RESERVED' => [
                            'data_type' => 'integer',
                        ]
                    ],
                    'select' => [
                        '*',
                        'QUANTITY_RESERVED',
                    ]
                ];
            }

            $result = StoreProductTable::getList($parameters);

            $rows = [];
            while ($row = $result->fetch()) {
                $rows[$row['PRODUCT_ID']][$row['STORE_ID']] = max(0, (float)$row['AMOUNT'] - (float)$row['QUANTITY_RESERVED']);
            }

            return $rows;
        }, $ids, null, [$active, $isIssuingCenter]);
    }
}