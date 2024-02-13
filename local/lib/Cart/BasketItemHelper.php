<?php

namespace Frizus\Cart;

use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\BasketPropertyItem;
use Bitrix\Sale\BasketPropertyItemBase;
use Frizus\IBlock\Product;
use Frizus\Module\Helper\Str;

class BasketItemHelper
{
    public const SYSTEM_PROPS = [
        'CATALOG.XML_ID',
        'PRODUCT.XML_ID',
    ];

    /**
     * @param BasketItem|array $basketItem
     */
    public static function url($basketItem)
    {
        if (!static::ofCatalog($basketItem)) {
            return;
        }

        return Product::getParentUrl(getField($basketItem, 'PRODUCT_ID'));
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function name($basketItem)
    {
        if (!static::ofCatalog($basketItem)) {
            return getField($basketItem, 'NAME');
        }

        return Product::getParentName(getField($basketItem, 'PRODUCT_ID')) ?: $basketItem->getField('NAME');
    }

    /**
     * @param BasketItem|array $basketItem
     * TODO добавить совместимость, когда передается $basketItem как массив
     */
    public static function props($basketItem, $force = true)
    {
        if (!static::ofCatalog($basketItem)) {
            return [];
        }

        $props = [];

        /** TODO здесь */
        /** @var BasketPropertyItem $basketPropertyItem */
        foreach ($basketItem->getPropertyCollection() as $basketPropertyItem) {
            if (static::isSystemProp($basketPropertyItem)) {
                continue;
            }
            $props[$basketPropertyItem->getField('NAME')] = $basketPropertyItem->getField('VALUE');
        }

        if ($force) {
            $props = [...Product::getSkuProps(getField($basketItem, 'PRODUCT_ID')) ?: [], ...$props];
        }

        return $props;
    }

    /**
     * @param BasketPropertyItem $basketPropertyItem
     */
    public static function isSystemProp($basketPropertyItem)
    {
        return in_array($basketPropertyItem->getField('CODE'), static::SYSTEM_PROPS, true);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function parentPicture($basketItem)
    {
        if (!static::ofCatalog($basketItem)) {
            return;
        }

        return Product::getParentPicturePreviewFirst(getField($basketItem, 'PRODUCT_ID'));
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function picture($basketItem)
    {
        if (!static::ofCatalog($basketItem)) {
            return;
        }

        return Product::getPicturePreviewFirst(getField($basketItem, 'PRODUCT_ID'));
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function measure($basketItem)
    {
        if ($measure = getField($basketItem, 'MEASURE_NAME')) {
            return $measure;
        }

        if (!static::ofCatalog($basketItem)) {
            return;
        }

        return Product::getMeasure(getField($basketItem, 'PRODUCT_ID'));
    }

    /**
     * @param BasketItem $basketItem
     */
    public static function regularBuyable($basketItem)
    {
        return static::ofCatalog($basketItem) &&
            static::buyable($basketItem);
    }

    public static function notRegularBuyable($basketItem)
    {
        return !static::regularBuyable($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function buyable($basketItem)
    {
        return is_true(getField($basketItem, 'CAN_BUY')) && not_true($basketItem, 'DELAY');
    }

    public static function notBuyable($basketItem)
    {
        return !static::buyable($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function ofCatalog($basketItem)
    {
        return getField($basketItem, 'MODULE') === 'catalog';
    }

    public static function notOfCatalog($basketItem)
    {
        return !static::ofCatalog($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function haveDiscount($basketItem)
    {
        return getFloatField($basketItem, 'BASE_PRICE') !== getFloatField($basketItem, 'PRICE');
    }

    public static function notHaveDiscount($basketItem)
    {
        return !static::haveDiscount($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function notCustomPrice($basketItem)
    {
        return not_true(getField($basketItem, 'CUSTOM_PRICE'));
    }

    public static function isCustomPrice($basketItem)
    {
        return !static::notCustomPrice($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function regularProductProvider($basketItem)
    {
        return Str::classMatch(\CCatalogProductProvider::class, getField($basketItem, 'PRODUCT_PROVIDER_CLASS')) ||
            Str::classMatch(CatalogProvider::class, getField($basketItem, 'PRODUCT_PROVIDER_CLASS'));
    }

    public static function notRegularProductProvider($basketItem)
    {
        return !static::regularProductProvider($basketItem);
    }

    public static function isNotRegularPrice($basketItem)
    {
        return !static::isRegularPrice($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function regularProduct($basketItem)
    {
        return static::ofCatalog($basketItem) &&
            static::regularProductProvider($basketItem);
    }

    public static function notRegularProduct($basketItem)
    {
        return !static::regularProduct($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function isRegularPrice($basketItem)
    {
        return static::regularProduct($basketItem) &&
            static::notCustomPrice($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function isRegularCustomPrice($basketItem)
    {
        return static::regularProduct($basketItem) &&
            static::isCustomPrice($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function notRegularPriceWithDiscounts($basketItem)
    {
        return static::notRegularProduct($basketItem) &&
            static::haveDiscount($basketItem);
    }

    /**
     * @param BasketItem|array $basketItem
     */
    public static function notRegularPriceWithoutDiscounts($basketItem)
    {
        return static::notRegularProduct($basketItem) &&
            static::notHaveDiscount($basketItem);
    }
}