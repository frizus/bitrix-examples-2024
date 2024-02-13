<?php
namespace Frizus\Delivery\MapPoints\Types;

use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Grid\Declension;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Sale\BasketItem;
use Frizus\Arr;
use Frizus\Cart\CartHelper;
use Frizus\IBlock\Product;

class Stores extends AbstractDeliveryPoints
{
    public function getPoints()
    {
        // ...

        $points = [];
        $currentLocation = null;
        foreach ($rows as $row) {
            $point = [
                'id' => $row['ID'],
                'lat' => $row['GPS_N'] ?: null,
                'lon' => $row['GPS_S'] ?: null,
                'name' => $row['TITLE'],
                'address' => $row['ADDRESS'],
                'description' => $row['DESCRIPTION'],
                'schedule' => $row['SCHEDULE'],
                'city' => $row['UF_CITY'],
            ];
            if ($this->pointId &&
                ((int)$this->pointId == $row['ID']) &&
                $row['GPS_N'] &&
                $row['GPS_S']
            ) {
                $currentLocation = [
                    $row['GPS_N'],
                    $row['GPS_S'],
                ];
            }

            $point += $availability[$row['ID']];
            $points[] = $point;
        }

        $this->sortPoints($points);

        return [
            ...parent::getPoints(),
            ...[
                'currentLocation' => $currentLocation,
                'points' => $points,
            ]
        ];
    }

    protected function sortPoints(&$points)
    {
        $i = 0;
        $cities = [];
        $firstSort = null;
        foreach ($points as $point) {
            if (!key_exists($point['city'], $cities)) {
                $cities[$point['city']] = ++$i;
                if (is_null($firstSort)) {
                    $firstSort = $cities[$point['city']];
                }
            }
        }

        $i = 0;
        foreach ($points as &$point) {
            $point['sort'] = ++$i;
            $point['citySort'] = $cities[$point['city']];
        }
        unset($point);

        usort($points, function($a, $b) use($firstSort) {
            $aAvailable = $a['available'] > 0;
            $bAvailable = $b['available'] > 0;
            $sameCity = $a['citySort'] === $b['citySort'];
            $aIsMainCity = $a['citySort'] === $firstSort;
            $bIsMainCity = $b['citySort'] === $firstSort;
            $sameMainCity = $aIsMainCity && $bIsMainCity;
            $bothAvailable = $aAvailable && $bAvailable;

            if ($sameMainCity && $bothAvailable) {
                return $a['sort'] - $b['sort'];
            }

            if ($sameMainCity) {
                if ($aAvailable) {
                    return -1;
                }

                if ($bAvailable) {
                    return 1;
                }

                return $a['sort'] - $b['sort'];
            }

            if ($aIsMainCity) {
                return -1;
            }
            if ($bIsMainCity) {
                return 1;
            }

            if ($bothAvailable) {
                return $a['sort'] - $b['sort'];
            }
            if ($aAvailable) {
                return -1;
            }
            if ($bAvailable) {
                return 1;
            }
            if ($sameCity) {
                return $a['sort'] - $b['sort'];
            }
            return $a['citySort'] - $b['citySort'];
        });

        foreach ($points as &$point) {
            unset($point['sort'], $point['citySort']);
        }
        unset($point);
    }

    protected function getStores($storeIds)
    {
        global $DB;

        $filter = [
            '=ACTIVE' => true,
            '=ISSUING_CENTER' => true,
        ];
        if ($storeIds) {
            $filter['@ID'] = $storeIds;
        }

        $parameters = [
            'filter' => $filter,
            'select' => [
                '*',
                'UF_CITY',
            ],
            'runtime' => [
                new \Bitrix\Main\Entity\ExpressionField(
                    'MAIN_CITY_MATCHES',
                    'UF_CITY = \'Москва\'',
                ),
            ]
        ];

        /**
         * @see https://estrin.pw/bitrix-d7-snippets/s/runtime-sort/
         */
        if ($this->city) {
            $parameters['runtime'][] = new \Bitrix\Main\Entity\ExpressionField(
                'CURRENT_CITY_MATCHES',
                'UF_CITY = \'' . $DB->ForSQL($this->city) . '\'',
            );

            $parameters['order'] = ['CURRENT_CITY_MATCHES' => 'DESC', 'MAIN_CITY_MATCHES' => 'DESC', 'UF_CITY' => 'ASC', 'ID' => 'ASC'];
        } else {
            $parameters['order'] = ['MAIN_CITY_MATCHES' => 'DESC', 'UF_CITY' => 'ASC', 'ID' => 'ASC'];
        }

        $result = StoreTable::getList($parameters);

        $rows = [];
        while ($row = $result->fetch()) {
            $rows[$row['ID']] = $row;
        }

        return $rows;
    }
}