<?php

namespace Frizus\Cart;

use Bitrix\Catalog\ProductTable;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Basket\Storage;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\Registry;
use Frizus\IBlock\ElementQuery;
use Frizus\IBlock\IBlock;
use Frizus\IBlock\Product;
use Frizus\Module\Helper\SiteHelper;
use Frizus\Module\Helper\UserHelper;
use Frizus\Helpers\Arr;

class CartHelper
{
    public const TEMPORARY_TYPE = 'temporary';

    public const FROM_CART_TYPE = 'from_cart';

    public static function getCart()
    {
        return Basket::loadItemsForFUser(Fuser::getId(), SiteHelper::getSiteId());
    }

    public static function getBuyableCart()
    {
        return static::getCart()->getOrderableItems();
    }

    public static function getStorageBasket()
    {
        /** @var Storage $basketStorate */
        $basketStorate = Storage::getInstance(Fuser::getId(), SiteHelper::getSiteId());
        $basket = $basketStorate->getBasket();
        return $basket;
    }

    public static function createTemporaryOrderForCart(Basket $basket, $userId = null)
    {
        /** @var BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            break;
        }

        $registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
        /** @var Order $orderClass */
        $orderClass = $registry->getOrderClassName();

        $userId ??= UserHelper::isAuthorized() ? UserHelper::getUserId() : \CSaleUser::GetAnonymousUserID();

        $order = $orderClass::create(
            SiteHelper::getSiteId(),
            $userId,
            $basketItem->getCurrency()
        );

        if (!$order->appendBasket($basket)->isSuccess()) {
            throw new \Exception('append basket fail');
        }

        return $order;
    }

    public static function addToUserBasket($productId)
    {
        if (!($product = static::prepareForAddingToBasket($productId))) {
            throw new \Exception('Не удалось составить массив товара с sku свойствами, чтобы добавить в корзину');
        }

        $result = \Bitrix\Catalog\Product\Basket::addProduct($product);
        if (!$result->isSuccess()) {
            throw new \Exception('Не удалось добавить товар в корзину');
            //throw new \Exception(print_r($result->getErrorMessages(), true));
        }

        return true;
    }

    /**
     * @see \Bitrix\Iblock\Component\Base::addProductToBasket
     *
     * @see \Bitrix\Iblock\Component\Base::initIblockPropertyFeatures
     */
    public static function prepareForAddingToBasket($productId, $fields = [])
    {
        if (!$productId ||
            !($row = ElementQuery::getProducts($productId, true, true))
        ) {
            return;
        }

        $rows = [&$row];
        Product::modifyAddRelationToParentFromOffer($rows);

        if (!($basketPropertyCodes = IBlock::getBasketPropertyCodes($row['MAIN_IBLOCK_ID']))) {
            return;
        }

        $productProperties = null;

        if ($row['TYPE'] == ProductTable::TYPE_OFFER) {
            Product::modifyAddSkuRelated($rows, IBlock::skuProps($row['MAIN_IBLOCK_ID']));
            if ($basketPropertyCodes['OFFERS_CART_PROPERTIES'] || $row['OFFERS_PROP_CODES']) {
                $productProperties = \CIBlockPriceTools::GetOfferProperties(
                    $productId,
                    $row['MAIN_IBLOCK_ID'],
                    $basketPropertyCodes['OFFERS_CART_PROPERTIES'],
                    $row['OFFERS_PROP_CODES']
                );
            }
        } else {
            if ($basketPropertyCodes['CART_PROPERTIES']) {
                $tempProductProperties = \CIBlockPriceTools::GetProductProperties(
                    $row['MAIN_IBLOCK_ID'],
                    $productId,
                    $basketPropertyCodes['CART_PROPERTIES'],
                    $row['PROPERTIES']
                );

                if ($tempProductProperties) {
                    $productProperties = $productPropertiesFill = \CIBlockPriceTools::getFillProductProperties($tempProductProperties);
                    foreach ($productPropertiesFill as $propId => $item) {
                        $productProperties[$propId] = $item['ID'];
                    }
                }

                if (is_array($productProperties)) {
                    $productProperties = \CIBlockPriceTools::CheckProductProperties(
                        $row['MAIN_IBLOCK_ID'],
                        $productId,
                        $basketPropertyCodes['CART_PROPERTIES'],
                        $productProperties,
                        true
                    );
                }
                if (!is_array($productProperties)) {
                    return;
                }
            }
        }

        $product = [
            'PRODUCT_ID' => (int)$productId,
        ];
        if (!$fields ||
            !key_exists('QUANTITY', $fields)
        ) {
            $product['QUANTITY'] = Product::getMinimalQuantity($productId);
        }
        if ($productProperties) {
            $product['PROPS'] = $productProperties;
        }
        if ($fields) {
            $product += $fields;
        }
        return $product;
    }

    /**
     * @param Basket|array $basket
     */
    public static function getProductIds($basket)
    {
        return array_keys(static::getBasketItemIndexesGroupedByProductId($basket));
    }

    /**
     * @param Basket|array $basket
     */
    public static function getBasketItemIndexesGroupedByProductId($basket)
    {
        $productIds = [];

        /**
         * @var BasketItem|array $basketItem
         */
        foreach ($basket as $index => $basketItem) {
            if (BasketItemHelper::ofCatalog($basketItem)) {
                $productIds[getField($basketItem, 'PRODUCT_ID')][$index] = $index;
            }
        }

        return $productIds;
    }

    /**
     * @param Basket|array $basket
     */
    public static function getProductsQuantities($basket)
    {
        $quantities = [];

        /**
         * @var BasketItem|array $basketItem
         */
        foreach ($basket as $index => $basketItem) {
            $quantities[getField($basketItem, 'PRODUCT_ID')] ??= 0.0;
            $quantities[getField($basketItem, 'PRODUCT_ID')] += getFloatField($basketItem, 'QUANTITY');
        }

        return $quantities;
    }

    public static function isTemporaryBasketCode($basketCode)
    {
        return preg_match('#^n[0-9]+$#', $basketCode);
    }

    /**
     * @param Basket|array $basket
     * @return array<temporary-или-from_cart, array<product_id, array<basket_code, basket_code>>>
     */
    public static function &getBasketItemIndicesGroupedByProductIdSplitByBasketCodeType($basket)
    {
        $result = static::getBasketItemIndexesGroupedByProductId($basket);
        $result = &Arr::groupBy($result, [function($item, $key) {
            return static::getBasketCodeType($key);
        }]);

        return $result;
    }

    public static function &splitByBasketCodeType($rows)
    {
        $result = &Arr::groupBy($rows, [function($item, $key) {
            return static::getBasketCodeType($key);
        }]);

        return $result;
    }

    public static function getBasketCodeType($basketCode)
    {
        return static::isTemporaryBasketCode($basketCode) ? static::TEMPORARY_TYPE : static::FROM_CART_TYPE;
    }

    public static function &filterOutTemporaryBasketCodes($rows)
    {
        return static::keepBasketCodesOfType($rows, static::FROM_CART_TYPE);
    }

    public static function &filterOutFromCartTypeBasketCodes($rows)
    {
        return static::keepBasketCodesOfType($rows, static::TEMPORARY_TYPE);
    }

    public static function &keepBasketCodesOfType($rows, $type)
    {
        $groups = &static::splitByBasketCodeType($rows);

        if (isset($groups[$type])) {
            return $groups[$type];
        }

        $result = [];
        return $result;
    }

    /**
     * @param Basket|array $basket
     */
    public static function getBasketItemByCode($basket, $basketCode)
    {
        static $assoc = [];

        if (is_array($basket)) {
            if (Arr::isAssoc($basket)) {
                return $basket[$basketCode];
            }
            /*if (CartHelper::isTemporaryBasketCode($basketCode)) {
                return $basket[$basketCode];
            }*/

            foreach ($basket as $basketItem) {
                if (getField($basketItem, 'ID') == $basketCode) {
                    return $basketItem;
                }
            }

            return;
        }

        return $basket->getItemByBasketCode($basketCode);
    }

    /**
     * @param BasketItem|array $basketItem
     * @param $index
     * @param Basket|array $basket
     */
    public static function getBasketItemCode($basketItem, $index, &$basket = null)
    {
        if (is_array($basketItem)) {
            if (isset($basket)) {
                if (Arr::isAssoc($basket)) {
                    return $index;
                }

                return getField($basket[$index], 'ID');
            }
            return $index;
        }

        return $basketItem->getBasketCode();
    }
}