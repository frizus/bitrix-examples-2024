<?php
namespace Frizus\Delivery\PreviewInfo\Types;

class Couriers extends AbstractDeliveryType
{
    public function getInfo()
    {
        return [
            ...parent::getInfo(),
            ...[
                'countText' => 'Курьерская доставка',
            ]
        ];
    }
}