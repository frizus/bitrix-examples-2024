<?php
namespace Frizus\Delivery\PreviewInfo\Types;

use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\StoreTable;

abstract class AbstractDeliveryType implements IDeliveryType
{
    protected $city;

    public function __construct($productIds, $city = null)
    {
        // ...
        $this->city = $city;
    }

    protected function getName() {
        $path = explode('\\', static::class);
        return array_pop($path);
    }

    public function getInfo()
    {
        return [
            'deliveryType' => $this->getName(),
            'count' => null,
            'countText' => null,
            'extra' => null,
            'extraText' => null,
            'price' => null,
            'priceText' => '',
            'deliveryDate' => null,
            'deliveryDateText' => null,
        ];
    }
}