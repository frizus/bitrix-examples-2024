<?php
namespace Frizus\Delivery\PreviewInfo;

use Frizus\Delivery\PreviewInfo\Types\Couriers;
use Frizus\Delivery\PreviewInfo\Types\DeliveryServicePoints;
use Frizus\Delivery\PreviewInfo\Types\IDeliveryType;
use Frizus\Delivery\PreviewInfo\Types\Stores;

class DeliveryPreviewInfoListService
{
    protected const DEFAULT_LIST = [
        Stores::class,
        DeliveryServicePoints::class,
        Couriers::class
    ];

    /** @var   */
    protected $list;

    public function __construct($list = null)
    {
        $this->list = $list ?? static::DEFAULT_LIST;
    }

    public function deliveryInfo($productIds, $city = null, $list = null)
    {
        $currentList = $list ?? $this->list;
        $result = [];

        /** @var IDeliveryType $class */
        foreach ($currentList as $class) {
            $deliveryType = new $class($productIds, $city);
            if ($deliveryInfo = $deliveryType->getInfo()) {
                $result[] = $deliveryInfo;
            }
        }

        return $result;
    }
}