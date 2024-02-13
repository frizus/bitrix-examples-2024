<?php

namespace Frizus\Traits;

trait FilterTrait
{
    public static function activeAvailableFilter($active, $catalogAvailable)
    {
        $filter = static::activeFilter($active);
        if (!is_null($catalogAvailable)) {
            $filter['CATALOG_AVAILABLE'] = to_YN($catalogAvailable);
        }

        return $filter;
    }

    protected static function activeFilter($active)
    {
        $filter = [];
        if (!is_null($active)) {
            $filter['ACTIVE'] = $filter['ACTIVE_DATE'] = to_YN($active);
        }

        return $filter;
    }
}