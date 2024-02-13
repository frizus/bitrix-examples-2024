<?php
namespace Frizus\Module\Rule;

use Bitrix\Main\Config\Option;
use Frizus\Module\Helper\Phone;
use Frizus\Module\HttpRequests\RecaptchaRequest;
use Frizus\Module\Rule\Base\Rule;
use Frizus\Module\Rule\Base\ValidatorAwareRule;
use Frizus\Module\Validation\Validator;

class RecaptchaRule extends Rule
{
    use ValidatorAwareRule;

    protected $message = 'Вы не прошли проверку';

    protected $action;

    protected $passScore;

    protected $invalidPassScore;

    protected $disabled;

    public function __construct($action, $passScore = 0.5, $disabled = false)
    {
        $this->action = $action;
        $this->invalidPassScore = filter_var($passScore, FILTER_VALIDATE_FLOAT) === false;
        if (!$this->invalidPassScore) {
            $this->passScore = floatval($passScore);
        }
        $this->disabled = $disabled;
    }

    public function passes($attribute, $value, $keyExists)
    {
        if ($this->disabled) {
            return true;
        }

        if (!$keyExists ||
            is_null($value) ||
            !is_string($value) ||
            ($value === '')
        ) {
            return false;
        }

        if ($this->invalidPassScore) {
            $this->setMessage('Некорректные настройки проверки на стороне сервера');
            return false;
        }

        $secret = Option::get('frizus.module', 'recaptcha_secret_key');

        if (!$secret) {
            $this->setMessage('Некорректные настройки проверки на стороне сервера');
            return false;
        }

        $request = new RecaptchaRequest();
        if (!$request->request($secret, $value)) {
            $this->setMessage('Не удалось провести проверку reCAPTCHA');
            return false;
        }

        $result = $request->getResult();
        if (($result['success'] === true) && ($result['action'] === $this->action)) {
            $score = floatval($result['score']);
            if ($score > $this->passScore) {
                return true;
            }
        }

        return false;
    }
}