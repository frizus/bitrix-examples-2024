<?php
namespace Frizus\Module\Request\Base;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Frizus\Module\Validation\ValidationException;
use Frizus\Module\Validation\Validator;

class AjaxRequest400 extends AjaxRequest
{
    protected function failedValidation(Validator $validator)
    {
        Application::getInstance()->getContext()->getResponse()->setStatus('400 Bad Request');
        Application::getInstance()->end();
    }

    public function getValidatorInstance()
    {
        $isset = isset($this->validator);
        parent::getValidatorInstance();

        if (!$isset) {
            $this->validator->stopOnFirstFailure();
        }

        return $this->validator;
    }
}