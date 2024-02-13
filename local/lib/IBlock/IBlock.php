<?php
namespace Frizus\IBlock;

use Bitrix\Catalog\Product\PropertyCatalogFeature;

class IBlock
{
    public static function skuProps($ids)
    {
        return getAndCachePerKey(function($id) {
            if (!($sku = static::getInfoByProductIBlock($id)) ||
                !($offerTreeProps = static::offerTreeProps($id)) ||
                !($skuPropList = \CIBlockPriceTools::getTreeProperties(
                    $sku,
                    $offerTreeProps,
                    false
                ))
            ) {
                return;
            }

            return $skuPropList;
        }, $ids);
    }

    public static function getBasketPropertyCodes($ids)
    {
        return getAndCachePerKey(function($id) {
            if (!($row = static::getInfoByIBlocks($id))) {
                return;
            }

            switch ($row['CATALOG_TYPE']) {
                case \CCatalogSku::TYPE_CATALOG:
                    $list = PropertyCatalogFeature::getBasketPropertyCodes($id, ['CODE' => 'Y']);
                    return [
                        'CART_PROPERTIES' => $list ?? [],
                        'OFFERS_CART_PROPERTIES' => [],
                    ];
                    break;
                case \CCatalogSku::TYPE_PRODUCT:
                    if ($row = static::getInfoByProductIBlock($id)) {
                        $list = PropertyCatalogFeature::getBasketPropertyCodes(
                            $row['IBLOCK_ID'],
                            ['CODE' => 'Y']
                        );
                    }
                    return [
                        'CART_PROPERTIES' => [],
                        'OFFERS_CART_PROPERTIES' => $list ?? [],
                    ];
                    break;
                case \CCatalogSku::TYPE_FULL:
                    $list = PropertyCatalogFeature::getBasketPropertyCodes($id, ['CODE' => 'Y']);
                    if ($row = static::getInfoByProductIBlock($id)) {
                        $list2 = PropertyCatalogFeature::getBasketPropertyCodes(
                            $row['IBLOCK_ID'],
                            ['CODE' => 'Y']
                        );
                    }

                    return [
                        'CART_PROPERTIES' => $list ?? [],
                        'OFFERS_CART_PROPERTIES' => $list2 ?? [],
                    ];
                    break;
                case \CCatalogSku::TYPE_OFFERS:
                    return [
                        'CART_PROPERTIES' => [],
                        'OFFERS_CART_PROPERTIES' => [],
                    ];
                    break;
                default:
                    return [
                        'CART_PROPERTIES' => [],
                        'OFFERS_CART_PROPERTIES' => [],
                    ];
                    break;
            }
        }, $ids);
    }

    public static function offerTreeProps($ids)
    {
        return getAndCachePerKey(function($id) {
            if (!($row = static::getInfoByIBlocks($id))) {
                return;
            }

            switch ($row['CATALOG_TYPE']) {
                case \CCatalogSku::TYPE_CATALOG:
                case \CCatalogSku::TYPE_OFFERS:
                    return [];
                    break;
                case \CCatalogSku::TYPE_PRODUCT:
                case \CCatalogSku::TYPE_FULL:
                    $list = PropertyCatalogFeature::getOfferTreePropertyCodes(
                        $row['IBLOCK_ID'],
                        ['CODE' => 'Y']
                    );
                    return $list ?? [];
                    break;
                default:
                    break;
            }
        }, $ids);
    }

    public static function getInfoByIBlocks($ids)
    {
        return getAndCachePerKey(function($id) {
            $row = \CCatalogSku::GetInfoByIBlock($id);

            if (!empty($row) && is_array($row)) {
                return $row;
            }
        }, $ids);
    }

    public static function getInfoByProductIBlock($ids)
    {
        return getAndCachePerKey(function($id) {
            $row = \CCatalogSku::GetInfoByProductIBlock($id);

            if (!empty($row) && is_array($row)) {
                return $row;
            }
        }, $ids);
    }
    
    public static function getInfoByOfferIBlock($ids)
    {
        return getAndCachePerKey(function($id) {
            $row = \CCatalogSku::GetInfoByOfferIBlock($id);

            if (!empty($row) && is_array($row)) {
                return $row;
            }
        }, $ids);
    }


}