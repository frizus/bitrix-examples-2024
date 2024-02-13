<?php
namespace Frizus\Module\Rule;

use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Frizus\Module\Helper\Phone;
use Frizus\Module\Rule\Base\Rule;
use Frizus\Module\Rule\Base\ValidatorAwareRule;
use Frizus\Module\Validation\Validator;

class DateRule extends Rule
{
    use ValidatorAwareRule;

    protected $message = 'Некорректная дата';

    protected $format;

    protected $birthday = false;

    protected $min;

    protected $max;

    protected $timezone;

    public function __construct($format = 'd.m.Y', $birthday = false, $min = null, $max = null, $timezone = null)
    {
        $this->format = $format ?? 'd.m.Y';
        $this->birthday = $birthday;
        $this->min = $min;
        $this->max = $max;
        $this->timezone = $timezone;
    }

    public function passes($attribute, $value, $keyExists)
    {
        if (!$keyExists ||
            is_null($value) ||
            !is_string($value) ||
            ($value === '')
        ) {
            return false;
        }

        try {
            $value = new DateTime($value, $this->format, $this->timezone);
            $value->setTime(0, 0, 0);
        } catch (SystemException $e) {
            return false;
        }

        if ($this->birthday) {
            $now = (new DateTime(null, null, $this->timezone))->setTime(0, 0, 0);
            //echo $value->format('d.m.Y H:i:s') . ' ' . $now->format('d.m.Y H:i:s') . ' ' . $value->getTimestamp() . ' ' . $now->getTimestamp() . ' ' . var_export($value->getTimestamp() < $now->getTimestamp(), true);die();

            if (!($value < $now)) {
                $this->setMessage('Некорректная дата рождения', false);
                return false;
            }
        }

        if (isset($this->min)) {
            if ($this->min > $value) {
                //$this->setMessage($this->min->format($this->format . ' H:i:s') . ' > ' . $value->format($this->format . ' H:i:s'));
                $this->setMessage('Дата раньше ' . $this->min->format($this->format));
                return false;
            }
        }

        if (isset($this->max)) {
            if ($this->max < $value) {
                $this->setMessage('Дата позже ' . $this->max->format($this->format));
                return false;
            }
        }

        return true;
    }
}