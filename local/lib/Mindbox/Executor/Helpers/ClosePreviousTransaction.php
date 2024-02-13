<?php

namespace Frizus\Mindbox\Executor\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Order;
use Frizus\Mindbox\Executor\MindboxExecutor;
use Frizus\Mindbox\Executor\Operations\DataGeneratedInRealTime\RollbackTransactionOperation;
use Frizus\Mindbox\MindboxHelper;
use Frizus\Module\Helper\HLBlockHelper;

class ClosePreviousTransaction
{
    public const WAIT_AFTER_CLOSE = 4;

    public static function havePreviousTransaction($order)
    {
        // ...
    }

    public static function sleep($time = self::WAIT_AFTER_CLOSE)
    {
        // ...
    }

    protected static function waitAfterLastClosedTransaction($order)
    {
        // ...
    }

    protected static function closedRecently($row)
    {
        // ...
    }

    public static function closePreviousTransaction($order, $sleep = true)
    {
        // ...
    }

    public static function getTransactions($order)
    {
        // ...
    }
}