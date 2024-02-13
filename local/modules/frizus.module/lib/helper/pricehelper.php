<?
namespace Frizus\Module\Helper;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;

class PriceHelper
{
    public static function getPrice($id, $iblockId, $type, $userGroups = null)
    {
        if (!is_null($price = self::_getPrice($id, $iblockId, $type, $userGroups))) {
            return (float)$price['RESULT_PRICE']['DISCOUNT_PRICE'];
        }

        return null;
    }

    public static function _getPrice($id, $iblockId, $type, $userGroups = null)
    {
        $id = (int)$id;
        $iblockId = (int)$iblockId;
        $type = (int)$type;

        if (!($id > 0) ||
            !($iblockId > 0)
        ) {
            return null;
        }

        if (!isset($userGroups)) {
            $userGroups = UserHelper::getAllUsersGroups();
        }

        if (($type === ProductTable::TYPE_PRODUCT) ||
            ($type === ProductTable::TYPE_OFFER)
        ) {
            $price = \CCatalogProduct::GetOptimalPrice($id, 1, $userGroups, 'N', [], SiteHelper::getSiteId(), []);
            if (!$price || !isset($price['RESULT_PRICE'])) {
                return null;
            }

            return $price;
        }

        if ($type === ProductTable::TYPE_SKU) {
            $result = \CCatalogSKU::getOffersList(
                $id,
                $iblockId,
                [
                    'ACTIVE' => 'Y',
                    '=AVAILABLE' => 'Y',
                ]
            );

            if (!$result ||
                !$result[$id] ||
                !is_array($result[$id]) ||
                empty($result[$id])
            ) {
                return null;
            }

            $finalPrices = [];

            foreach ($result[$id] as $offer) {
                $price = \CCatalogProduct::GetOptimalPrice($offer['ID'], 1, $userGroups, 'N', [], SiteHelper::getSiteId(), []);
                if (!$price || !isset($price['RESULT_PRICE'])) {
                    continue;
                }
                $finalPrices[$offer['ID']] = $price;
            }

            if (empty($finalPrices)) {
                return null;
            }

            $price = null;
            foreach($finalPrices as $finalPrice) {
                if (!isset($price) || $price['RESULT_PRICE']['DISCOUNT_PRICE'] > $finalPrice['RESULT_PRICE']['DISCOUNT_PRICE']) {
                    $price = $finalPrice;
                }
            }

            return $price;
        }

        return null;
    }
}