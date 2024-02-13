<?
namespace Frizus\Module\Helper;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;

class OrderHelper
{
    public static function paymentNeedHashInReturnUrl($order)
    {
        return $order &&
            \Bitrix\Sale\Helpers\Order::isAllowGuestView($order) &&
            !OrderHelper::userCanViewOrder($order) &&
            (
                OrderHelper::hashMatches($order, $_REQUEST['HASH']) ||
                OrderHelper::guestCanViewOrder($order)
            );
    }

    /**
     * @param Order $order
     * @param Payment $paymentItem
     * @param $hash
     */
    public static function getPaymentUrl($order, $paymentItem = false, $hash = false)
    {
        $url = '/cart/ordering/payment/?ORDER_ID=' . $order->getField('ACCOUNT_NUMBER');

        if ($paymentItem) {
            $url .= '&PAYMENT_ID=' . $paymentItem->getField('ACCOUNT_NUMBER');
        }

        if (self::userCanViewOrder($order)) {
            return $url;
        }

        if (self::hashMatches($order, $hash)) {
            return $url . '&HASH=' . $hash;
        }

        return $url;
    }

    /**
     * @param Order $order
     */
    public static function getOrderUrl($order, $hash = false, $checkBySession = false)
    {
        $url = '/personal/orders/' . $order->getField('ACCOUNT_NUMBER') . '/';

        if (self::userCanViewOrder($order)) {
            return $url;
        }

        if ((
                $hash &&
                self::hashMatches($order, $hash)
            ) ||
            (
                $checkBySession &&
                self::guestCanViewOrder($order)
            )
        ) {
            return self::getOrderGuestUrl($order);
        }

        return $url;
    }

    /**
     * @param Order $order
     */
    public static function canViewOrder($order, $hash = false, $checkBySession = false)
    {
        return self::userCanViewOrder($order) ||
            ($hash && self::hashMatches($order, $hash)) ||
            (
                $checkBySession &&
                self::guestCanViewOrder($order)
            );
    }

    public static function userCanViewOrder($order)
    {
        global $USER;

        return $order &&
            $USER->IsAuthorized() &&
            ((int)$order->getUserId() === (int)$USER->GetID());
    }

    /**
     * @param Order $order
     */
    public static function hashMatches($order, $hash)
    {
        return $hash &&
            $order &&
            \Bitrix\Sale\Helpers\Order::isAllowGuestView($order) &&
            ($order->getHash() === $hash);
    }

    /**
     * @param Order $order
     */
    public static function guestCanViewOrder($order)
    {
        global $USER;

        if (!$order) {
            return false;
        }

        $checkedBySession = false;
        $session = Application::getInstance()->getSession();
        if ($session->isAccessible())
        {
            if (isset($session['SALE_ORDER_ID']) &&
                is_array($session['SALE_ORDER_ID'])
            ) {
                $checkedBySession = in_array($order->getId(), $session['SALE_ORDER_ID']);
            }
        }
        unset($session);

        return
            (
                (
                    $USER->IsAuthorized() &&
                    ($order->getUserId() != $USER->GetID())
                ) ||
                !$USER->IsAuthorized()
            ) &&
            $checkedBySession;
    }

    /**
     * @param Order $order
     */
    public static function getOrderGuestUrl($order)
    {
        if (!$order ||
            !\Bitrix\Sale\Helpers\Order::isAllowGuestView($order)
        ) {
            return false;
        }

        $paths = unserialize(Option::get("sale", "allow_guest_order_view_paths"), ['allowed_classes' => false]);
        $path = htmlspecialcharsbx($paths[(defined('ADMIN_SECTION') && ADMIN_SECTION) ? 's1' : SITE_ID]);

        /** @see \Bitrix\Sale\Helpers\Order::getPublicLink(); */
        if (isset($path) && mb_strpos($path, '#order_id#')) {
            $accountNumber = urlencode(urlencode($order->getField('ACCOUNT_NUMBER')));
            $path = str_replace('#order_id#', $accountNumber, $path);
            if (mb_strpos($path, '/') !== 0) {
                $path = '/' . $path;
            }

            $path .= (mb_strpos($path, '?')) ? '&' : "?";
            $hash = $order->getHash();
            $path .= "access=" . htmlspecialcharsbx($hash);
            return $path;
        }

        return false;
    }
}