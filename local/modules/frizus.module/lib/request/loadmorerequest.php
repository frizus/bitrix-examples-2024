<?php
namespace Frizus\Module\Request;

use Frizus\Module\Request\Base\AjaxRequest;
use Frizus\Module\Request\Base\AjaxRequest400;
use Frizus\Module\Request\Traits\CheckAuthorization;
use Frizus\Module\Rule\EmailRule;
use Frizus\Module\Rule\EmptyStringRule;
use Frizus\Module\Rule\PhoneRule;

class LoadMoreRequest extends AjaxRequest400
{
    public function validationData()
    {
        return $this->requestData(null, ['last_id'], $this->postInputGroup, $this->excludeFromGroup);
    }

    public function rules()
    {
        return [
            'last_id' => [
                'bail',
                'required',
                'notemptystring',
                'integer_id',
            ]
        ];
    }
}