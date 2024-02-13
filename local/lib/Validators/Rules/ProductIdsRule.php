<?php
namespace Frizus\Validators\Rules;

use Frizus\Module\Rule\Base\Rule;
use Frizus\Module\Rule\Base\ValidatorAwareRule;

class ProductIdsRule extends ElementIdsRule
{
    public function __construct($singleValue = false, $notOffer = false, $activeAvailable = null)
    {
        $iblockIds = [getCatalogIBlockId()];
        if (!$notOffer) {
            $iblockIds[] = getOffersIBlockId();
        }
        parent::__construct($singleValue, $iblockIds, $activeAvailable);
    }
}