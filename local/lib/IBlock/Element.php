<?php

namespace Frizus\IBlock;

use Frizus\IBlock\Traits\AvailableFieldAlgorithm;
use Frizus\Helpers\Arr;

class Element
{
    use AvailableFieldAlgorithm;

    public static function getUrl($ids)
    {
        return static::getField($ids, 'DETAIL_PAGE_URL');
    }

    public static function getName($ids)
    {
        return static::getField($ids, 'NAME');
    }

    public static function getPicture($ids)
    {
        return static::getPictureDetailFirst($ids);
    }

    public static function getPicturePreviewFirst($ids)
    {
        return static::getField($ids, ['PREVIEW_PICTURE_SRC', 'DETAIL_PICTURE_SRC']);
    }

    public static function getPictureDetailFirst($ids)
    {
        return static::getField($ids, ['DETAIL_PICTURE_SRC', 'PREVIEW_PICTURE_SRC']);
    }

    public static function getField($ids, $field)
    {
        return static::getFirstAvailableField($ids, $field, null);
    }

    public static function getFirstAvailableField($ids, $fields, $checkIfValueIsNullOrEmptyString = false)
    {
        return getAndCache(function($ids, $fields, $checkIfValueIsNullOrEmptyString) {
            $fields = Arr::wrap($fields);
            $rows = ElementQuery::getElements($ids);
            $result = [];

            foreach ($rows as $id => $row) {
                $passes = false;

                foreach ($fields as $field) {
                    [$passes, $value] = static::firstAvailableFieldAlgorithm($field, $row, $checkIfValueIsNullOrEmptyString);
                    if ($passes) {
                        break;
                    }
                }

                if (!$passes) {
                    continue;
                }

                $result[$id] = $value;
            }

            return $result;
        }, $ids, null, [$fields, $checkIfValueIsNullOrEmptyString]);
    }
}