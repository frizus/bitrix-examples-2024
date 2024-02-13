<?php
namespace Frizus\Delivery\MapPoints\Types;

use Bitrix\Catalog\StoreProductTable;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Fuser;
use Frizus\Cart\CartHelper;
use Frizus\Module\Helper\SiteHelper;

abstract class AbstractDeliveryPoints implements IDeliveryPoints
{
    public function __construct($productsGrouped, $type = null, $city = null, $pointId = null, $deliveryId = null)
    {
        // ...
    }

    protected function getName() {
        $path = explode('\\', static::class);
        return array_pop($path);
    }

    protected function getGroupType()
    {
        return $this->getName();
    }

    public function getPoints()
    {
        return [
            'groupType' => $this->getGroupType(),
            'deliveryType' => $this->getName(),
            'points' => [],
        ];
    }
}