<?
namespace Frizus\Module\Helper;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Frizus\Helpers\Arr;

class HLBlockHelper
{
    /**
     * @param DataManager $hlClass
     * @param $bulk
     * @param $parameters
     * @return EntityObject|void
     */
    public static function fetch($hlClass, $chunk = 200, $parameters = [], $selectId = true, $lastId = 0, $limit = false)
    {
        $lastId = (int)$lastId;
        $taken = 0;

        $parameters['order'] = [
            'ID' => 'ASC',
        ];
        Arr::forget($parameters, 'offset');
        if ($selectId) {
            $parameters['select'] = array_unique(array_merge($parameters['select'] ?? [], ['ID']));
        }

        while (true) {
            $lastTaken = $taken;
            $taken += $chunk;
            if ($limit && ($taken > $limit)) {
                $perQuery = $limit - $lastTaken;
            } else {
                $perQuery = $chunk;
            }

            $parameters['limit'] = $perQuery;
            $parameters['filter']['>ID'] = $lastId;
            $result = $hlClass::getList($parameters);
            $rows = $result->fetchCollection();

            if ($rows->count() === 0) {
                return;
            }

            $lastLastId = $lastId;

            foreach ($rows as $row) {
                if ($row['ID']) {
                    $lastId = $row['ID'];
                }

                yield $row;
            }

            if ($limit && ($taken >= $limit)) {
                return;
            }

            if ($lastLastId === $lastId) {
                return;
            }
        }
    }

    /**
     * @param $name
     * @return \Bitrix\Main\ORM\Data\DataManager|false
     */
    public static function getClass($name)
    {
        static $entityDataClasses = [];

        if (!$name) {
            return false;
        }

        if (!isset($entityDataClasses[$name])) {
            $hlblock = HighloadBlockTable::getList([
                'filter' => ['=NAME' => $name],
                'limit' => 1,
            ])->fetch();
            if ($hlblock) {
                $entity = HighloadBlockTable::compileEntity($hlblock);
                $entityDataClasses[$name] = $entity->getDataClass();
            } else {
                $entityDataClasses[$name] = false;
            }
        }

        return $entityDataClasses[$name];
    }
}