<?php
namespace Frizus\Mindbox\DataPreparer;

use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\Shipment;
use Frizus\Cart\CartProvider;
use Frizus\Cart\DiscountDetalization;
use Frizus\Cart\OrderHelper;
use Frizus\Mindbox\DataPreparer\Traits\Deliveries;
use Frizus\Mindbox\DataPreparer\Traits\Lines;
use Frizus\Mindbox\DataPreparer\Traits\Payments;
use Frizus\Mindbox\MindboxHelper;

class MindboxOrderDataForRequest
{
    use Lines;
    use \Frizus\Mindbox\DataPreparer\Traits\Order;
    use Payments;
    use Deliveries;

    /** @var CartProvider */
    protected $cartProvider;

    /** @var DiscountDetalization */
    protected $discounts;

    /**
     * @param Order $order
     */
    public function __construct(
        protected Order $order,
        $discounts = null
    )
    {
        if (!$this->discounts) {
            $this->discounts = DiscountDetalization::init(
                $this->order->getDiscount()->getApplyResult(false) ?: [],
                $this->order->getBasket(),
                $this->order->getCurrency(),
                OrderHelper::getDeliveryBasePrice($this->order),
                OrderHelper::getDeliveryCurrency($this->order)
            );
        }
        $this->cartProvider = new CartProvider($this->order, $this->discounts);
    }
}