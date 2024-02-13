<?php

namespace Frizus\Catalog;

use Bitrix\Catalog\StoreTable;
use Frizus\GetAndCache;

class StoreQuery
{
    public static function getActiveNotIssuingCenters($ids)
    {
        return static::getStores($ids, true, false);
    }

    public static function getNotIssuingCenters($ids)
    {
        return static::getStores($ids, null, false);
    }

    public static function getActiveIssuingCenters($ids)
    {
        return static::getStores($ids, true, true);
    }

    public static function getIssuingCenters($ids)
    {
        return static::getStores($ids, null, true);
    }

    public static function getActiveStores($ids)
    {
        return static::getStores($ids, true);
    }

    public static function getStores($ids, $active = null, $isIssuingCenter = null)
    {
        return getAndCache(function($ids, $active, $isIssuingCenter) {
            $filter = [];
            if (!is_null($active)) {
                $filter['=ACTIVE'] = to_bool($active);
            }
            if (!is_null($isIssuingCenter)) {
                $filter['=ISSUING_CENTER'] = to_bool($isIssuingCenter);
            }

            return static::queryStores($ids, $filter);
        }, $ids, null, [$active, $isIssuingCenter], getAlsoCacheAs($active, $isIssuingCenter));
    }

    protected static function queryStores($ids, $filter = null)
    {
        if (!$ids) {
            return [];
        }

        $result = StoreTable::getList([
            'filter' => [
                ...($filter ?: []),
                ...[
                    'ID' => array_values($ids),
                ]
            ],
            'select' => [
                '*',
            ]
        ]);

        $rows = [];
        while ($row = $result->fetch()) {
            $rows[$row['ID']] = $row;
        }

        return $rows;
    }
}