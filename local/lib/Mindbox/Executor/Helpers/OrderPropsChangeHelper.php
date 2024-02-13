<?php

namespace Frizus\Mindbox\Executor\Helpers;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Order;
use Frizus\Mindbox\MindboxHelper;

class OrderPropsChangeHelper
{
    public const YES = 'Да';

    public const AWAITING = 'Ожидается';

    public const NO = 'Нет';

    public const ORDER_IS_CREATED_IN_MINDBOX = [
        self::YES,
        self::AWAITING,
        self::NO,
    ];

    /**
     * @param Order $order
     */
    public static function setUpdatedAt($order)
    {
        if (!($property = $order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_UPDATED_AT'))) {
            return;
        }

        return $property->setValue((new DateTime())->format('d.m.Y H:i:s'))->isSuccess();
    }

    public static function setYesToOrderIsCreatedInMindbox($order)
    {
        return static::setOrderIsCreatedInMindbox($order, static::YES);
    }

    public static function setAwaitingToOrderIsCreatedInMindbox($order)
    {
        return static::setOrderIsCreatedInMindbox($order, static::AWAITING);
    }

    public static function setNoToOrderIsCreatedInMindbox($order)
    {
        return static::setOrderIsCreatedInMindbox($order, static::NO);
    }

    /**
     * @param Order $order
     */
    public static function setOrderIsCreatedInMindbox($order, $value)
    {
        if (!($property = $order->getPropertyCollection()->getItemByOrderPropertyCode('ORDER_IS_CREATED_IN_MINDBOX'))) {
            return;
        }

        if ($property->getValue() !== $value) {
            return $property->setValue($value)->isSuccess();
        }

        return null;
    }
}