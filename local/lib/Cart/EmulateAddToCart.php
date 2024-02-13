<?php

namespace Frizus\Cart;

use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Bitrix\Sale\Registry;
use Frizus\IBlock\Product;
use Frizus\Module\Helper\SiteHelper;
use Frizus\Helpers\Arr;

class EmulateAddToCart
{
    /**
     * @param $productIds
     * @return \Bitrix\Sale\Basket
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     *
     * @see \CAllCatalogProduct::getPossibleSalePrice
     */
    public static function emulateAddToCart($productIds)
    {
        \Bitrix\Sale\DiscountCouponsManagerBase::freezeCouponStorage();

        $registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
        /** @var Basket $basketClass */
        $basketClass = $registry->getBasketClassName();
        $basket = $basketClass::create(SiteHelper::getSiteId());
        if (!$productIds) {
            throw new \Exception('empty basket');
        }
        $productIds = Arr::wrap($productIds);

        foreach ($productIds as $productId) {
            $result = \Bitrix\Catalog\Product\Basket::addProductToBasket($basket, [
                'PRODUCT_ID' => $productId,
                'QUANTITY' => Product::getMinimalQuantity($productId),
            ], [
                'SITE_ID' => SiteHelper::getSiteId(),
            ]);

            if (!$result->isSuccess()) {
                throw new \Exception('emulate basket add fail');
            }
        }

        \Bitrix\Sale\DiscountCouponsManagerBase::unFreezeCouponStorage();

        return $basket;
    }

    /**
     * @param $productId
     * @return Order
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public static function emulateAddToCartWithOrder($productId)
    {
        \Bitrix\Sale\DiscountCouponsManagerBase::freezeCouponStorage();
        $order = CartHelper::createTemporaryOrderForCart(static::emulateAddToCart($productId));
        \Bitrix\Sale\DiscountCouponsManagerBase::unFreezeCouponStorage();
        return $order;
    }
}