<?php
namespace Frizus\Cart;

use Bitrix\Sale\Order;
use Bitrix\Sale\OrderStatus;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\Registry;
use Bitrix\Sale\Shipment;
use Frizus\Domain;

class OrderHelper
{
    public static $returnStatuses = [];

    /**
     * @param Order $order
     */
    public static function getDeliveryCurrency($order)
    {
        /** @var Shipment $shipment */
        foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment) {
            return $shipment->getCurrency();
        }
    }

    /**
     * @param Order $order
     */
    public static function getDeliveryBasePrice($order)
    {
        /** @var Shipment $shipment */
        foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment) {
            return $shipment->getField('BASE_PRICE_DELIVERY');
        }
    }

    public static function getFinalStatus()
    {
        $registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
        /** @var OrderStatus $orderStatus */
        $orderStatus = $registry->getOrderStatusClassName();
        $finalStatus = $orderStatus::getFinalStatus();

        return $finalStatus;
    }

    /**
     * @param Order|string $orderOrStatusId
     */
    public static function isFinalStatus($orderOrStatusId)
    {
        $statusId = is_object($orderOrStatusId) ? $orderOrStatusId->getField('STATUS_ID') : $orderOrStatusId;

        return $statusId === static::getFinalStatus();
    }

    public static function isCashPaid($orderOrStatusId)
    {
        return static::isFinalStatus($orderOrStatusId);
    }

    /**
     * @param Order $order
     */
    public static function isReturned($order, $statusId = null)
    {
        $statusId ??= $order->getField('STATUS_ID');

        return $order->isCanceled() ||
            static::isReturnStatus($statusId);
    }

    public static function isReturnStatus($orderOrStatusId)
    {
        $orderOrStatusId = is_object($orderOrStatusId) ? $orderOrStatusId->getField('STATUS_ID') : $orderOrStatusId;

        return in_array($orderOrStatusId, static::$returnStatuses, true);
    }
}