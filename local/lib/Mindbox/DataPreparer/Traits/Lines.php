<?php

namespace Frizus\Mindbox\DataPreparer\Traits;

use Bitrix\Sale\BasketItem;
use Frizus\Cart\CartProvider;
use Frizus\Cart\DiscountDetalization;
use Frizus\Module\Helper\CatalogHelper;

trait Lines
{
    /** @var CartProvider */
    protected $cartProvider;

    public function getLines($status = null)
    {
        $lines = [];
        $status ??= 'Create';

        $i = 1;
        /** @var BasketItem $basketItem */
        foreach ($this->cartProvider->getBasket() as $index => $basketItem) {
            $line = [
                'basePricePerItem' => $this->cartProvider->getBasePrice($basketItem->getBasketCode()),
                'quantity' => $basketItem->getQuantity(),
                'lineId' => $basketItem->getBasketCode(),
                'lineNumber' => $i++,
                'product' => [
                    'ids' => [
                        'externalId' => CatalogHelper::getExternalId($basketItem->getProductId()),
                    ]
                ],
                'status' => [
                    'ids' => [
                        'externalId' => $status
                    ]
                ],
                'requestedPromotions' => [],
            ];
            if ($basketItemDiscounts = $this->cartProvider->getBasketItemDiscounts($basketItem->getBasketCode(), $this->sendPriceTypeDiscount)) {
                $line['requestedPromotions'] = array_merge($line['requestedPromotions'], $basketItemDiscounts);
            }

            $lines[] = $line;
        }

        return $lines;
    }
}