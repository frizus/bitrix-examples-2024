<?php
namespace Frizus\Delivery\MapPoints;

use Bitrix\Sale\Basket;
use Frizus\City\City;
use Frizus\Delivery\MapPoints\Types\DeliveryServicePoints;
use Frizus\Delivery\MapPoints\Types\IDeliveryPoints;
use Frizus\Delivery\MapPoints\Types\Stores;

class DeliveryMapPointsListService
{
    protected const DEFAULT_LIST = [
        Stores::class,
        DeliveryServicePoints::class,
    ];

    /** @var   */
    protected $list;

    /** @var Basket */
    protected $basket;

    protected $type;

    protected $mode;

    protected $city;

    protected $pointId;

    public function __construct(Basket $basket = null, $type = null, $mode = null, $city = null, $pointId = null, $deliveryId = null, $list = null)
    {
        // ....
    }

    /**
     * @param Basket $basket
     * @param $list
     * @return array
     */
    public function mapPointsAndProducts($list = null)
    {
        // ...
    }
}