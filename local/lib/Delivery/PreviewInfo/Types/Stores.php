<?php
namespace Frizus\Delivery\PreviewInfo\Types;

use Bitrix\Main\Grid\Declension;
use Frizus\Delivery\PreviewInfo\Types\AbstractDeliveryType;

class Stores extends AbstractDeliveryType
{
    public function getInfo()
    {
        // ...

        return [
            ...parent::getInfo(),
            ...[
                'count' => $count,
                'countText' => $countText,
                'priceText' => $priceText,
                'deliveryDateText' => $deliveryDateText,
            ]
        ];
    }
}