<?php
namespace Frizus\Module\Request\Base;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Frizus\Module\Validation\ValidationException;
use Frizus\Module\Validation\Validator;

class AjaxRequest extends Request
{
    protected $validatorCalled;

    protected $postInputGroup;

    protected $excludeFromGroup = ['sessid'];

    public function __construct($postInputGroup = null, $step = null)
    {
        parent::__construct($step);
        $this->postInputGroup = $postInputGroup;
    }

    protected function failedValidation(Validator $validator)
    {
        Application::getInstance()->getContext()->getResponse()->addHeader('Content-Type', 'application/json; charset=UTF-8');
        $data = [
            'status' => 'error',
            'error' => $validator->messages()->messages(),
        ];
        foreach ($validator->extra() as $key => $extra) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $extra;
            }
        }
        echo json_encode($data);
        Application::getInstance()->end();
    }
}