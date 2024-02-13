<?
namespace Frizus\Module\Helper;

use Bitrix\Main\Loader;

class Element
{
    public static function fetch($iblockId, $chunk = 100, $filter = [], $select = [], $props = false, $selectId = true, $lastId = 0, $limit = false)
    {
        if (!$iblockId) {
            return;
        }

        $lastId = (int)$lastId;
        $taken = 0;

        if ($selectId) {
            $select = array_unique(array_merge($select ?? [], ['ID']));
        }

        $filter['IBLOCK_ID'] = $iblockId;
        $i = 0;

        while (true) {
            $lastTaken = $taken;
            $taken += $chunk;
            if ($limit && ($taken > $limit)) {
                $perQuery = $limit - $lastTaken;
            } else {
                $perQuery = $chunk;
            }

            $filter['>ID'] = $lastId;
            $result = \CIBlockElement::GetList(
                [
                    'ID' => 'asc',
                ],
                $filter,
                false,
                [
                    'nPageSize' => $perQuery,
                ],
                $select
            );

            $rows = [];

            while ($row = $result->GetNextElement(true, false)) {
                if ($props) {
                    $row = ['PROPERTIES' => $row->GetProperties()] + $row->GetFields();
                } else {
                    $row = $row->GetFields();
                }

                $rows[] = $row;
            }

            if (empty($rows)) {
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
}