<?php

namespace Frizus\Mindbox\Executor;

use Frizus\Mindbox\Executor\Helpers\QueueItemChangeHelper;
use Frizus\Mindbox\Executor\Operations\AbstractOperation;
use Frizus\Mindbox\Executor\Operations\DataFromDB\AbstractDataFromDBOperation;
use Frizus\Mindbox\Executor\Operations\DataFromDB\CommitOperation;
use Frizus\Mindbox\Executor\Operations\DataFromDB\ReturnOperation;
use Frizus\Mindbox\Executor\Operations\DataFromDB\RollbackTransactionOperation;
use Frizus\Mindbox\Executor\Operations\DataFromDB\SaveOfflineOrderOperation;
use Frizus\Mindbox\Executor\Operations\DataFromDB\UpdateOrderOperation;
use Frizus\Mindbox\Executor\Operations\DataGeneratedInRealTime\AbstractDataGeneratedInRealTimeOperation;
use Frizus\Mindbox\Executor\Operations\DataGeneratedInRealTime\BeginCreateOnlineOrderOperation;
use Frizus\Mindbox\MindboxHelper;
use Frizus\Module\Helper\HLBlockHelper;

class MindboxExecutor
{
    public const DATA_FROM_DB = '';

    public const DATA_GENERATED_FROM_ORDER = '';

    public const COMMIT = '';

    public const RETURN = '';

    public const ROLLBACK = '';

    public const SAVE_OFFLINE_ORDER = '';

    public const UPDATE_ORDER = '';

    public const BEGIN_CREATE_ONLINE_ORDER = '';

    public const CREATE_BEGIN_ORDER_TRANSACTION_INFO_FOR_UPDATE_ORDER = '';

    public const OPERATIONS = [
        self::DATA_FROM_DB => [
            self::COMMIT => CommitOperation::class,
            self::RETURN => ReturnOperation::class,
            self::ROLLBACK => RollbackTransactionOperation::class,
            self::SAVE_OFFLINE_ORDER => SaveOfflineOrderOperation::class,
            self::UPDATE_ORDER => UpdateOrderOperation::class,
        ],
        self::DATA_GENERATED_FROM_ORDER => [
            self::BEGIN_CREATE_ONLINE_ORDER => BeginCreateOnlineOrderOperation::class,
            self::COMMIT => \Frizus\Mindbox\Executor\Operations\DataGeneratedInRealTime\CommitOperation::class,
            self::RETURN => \Frizus\Mindbox\Executor\Operations\DataGeneratedInRealTime\ReturnOperation::class,
            self::UPDATE_ORDER => \Frizus\Mindbox\Executor\Operations\DataGeneratedInRealTime\UpdateOrderOperation::class,
        ]
    ];

    protected $lastId;

    protected $endTime;

    public function __construct(
        protected $time = 45
    )
    {
        $this->endTime = time() + $this->time;
    }

    protected function isTimeout()
    {
        $currentTime = time();

        return $currentTime >= $this->endTime;
    }

    /**
     * @param $lastId
     * @return AbstractDataFromDBOperation|null
     */
    public function getNextOperation()
    {
        if (!($class = HLBlockHelper::getClass('FrizusMindboxQueue')) ||
            $this->isTimeout()
        ) {
            return;
        }

        $result = $class::getList([
            'filter' => [
                '>ID' => (int)$this->lastId,
                '@UF_EXEC_STATUS' => [
                    QueueItemChangeHelper::NEED_TO_RUN,
                    QueueItemChangeHelper::WILL_BE_RUN_AGAIN,
                ]
            ],
            'limit' => 1,
            'order' => [
                'ID' => 'ASC',
            ]
        ]);

        if (!($row = $result->fetch())) {
            return;
        }

        $this->lastId = $row['ID'];

        if (!($operation = static::operation(static::DATA_FROM_DB, $row['UF_OPERATION'], $row))) {
            return;
        }

        return $operation;
    }

    public static function haveUnifinishedTasks($order, $excludeId = null)
    {
        // ...
    }

    public static function havePreviouslyUnfinishedCommitTask($websiteId, $currentTaskId)
    {
        // ...
    }

    /**
     * @return AbstractDataFromDBOperation|AbstractDataGeneratedInRealTimeOperation|null
     */
    public static function operation($operationSource, $operation, ...$args)
    {
        if (!($operationClass = static::OPERATIONS[$operationSource][$operation])) {
            return;
        }

        return new $operationClass(...$args);
    }
}