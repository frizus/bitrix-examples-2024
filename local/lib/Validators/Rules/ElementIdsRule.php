<?php
namespace Frizus\Validators\Rules;

use Frizus\IBlock\ElementQuery;
use Frizus\Arr;
use Frizus\Module\Rule\Base\Rule;
use Frizus\Module\Rule\Base\ValidatorAwareRule;

class ElementIdsRule extends Rule
{
    use ValidatorAwareRule;

    protected $iblockIds;

    protected $singleValue;

    protected $activeAvailable;

    public function __construct($singleValue, $iblockIds, $activeAvailable)
    {
        $this->singleValue = $singleValue;
        $this->iblockIds = Arr::wrap($iblockIds);
        $this->activeAvailable = $activeAvailable;
    }

    public function passes($attribute, $value, $keyExists)
    {
        if (!$keyExists ||
            !$value
        ) {
            return false;
        }

        if (!$this->singleValue) {
            if (!is_array($value) ||
                empty($value) ||
                (count($value) !== count(array_unique($value))) ||
                !Arr::isIndexed($value)
            ) {
                return false;
            }
        } else {
            $value = [$value];
        }

        if (!$this->iblockIds) {
            $this->setMessage('Не настроен сайт');
            return false;
        }

        foreach ($value as $productId) {
            if ((filter_var($productId, FILTER_VALIDATE_INT) === false) ||
                !((int)$productId > 0)
            ) {
                return false;
            }
        }

        $oldValue = $value;
        $value = array_combine($value, $value);

        $rows = ElementQuery::getProducts($value, true, $this->activeAvailable);
        foreach ($rows as $row) {
            if (in_array($row['IBLOCK_ID'], $this->iblockIds)) {
                unset($value[$row['ID']]);
            }
        }

        if (!empty($value)) {
            return false;
        }

        if (!$this->singleValue) {
            sort($oldValue, SORT_NATURAL);
            $this->input($attribute, $oldValue);
        }

        return true;
    }
}