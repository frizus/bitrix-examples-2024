<?php
namespace Frizus\Module\Rule\Base;

use Frizus\Module\Validation\Validator;

trait ValidatorAwareRule
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @param  Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }
}