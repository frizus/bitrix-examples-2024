<?php
namespace Frizus\Module\Validation\Traits;

trait FormatsMessages
{
    protected function makeReplacements($message, $attribute)
    {
        return str_replace(':attribute', $attribute, $message);
    }

    protected function getMessage($attribute, $rule)
    {
        $lowerRule = strtolower($rule);
        $keys = ["$attribute:$lowerRule", $attribute, ':' . $lowerRule];

        foreach ($keys as $key) {
            if (array_key_exists($key, $this->customMessages)) {
                return $this->customMessages[$key];
            }
        }

        return 'Валидация ' . $key . ' провалилась.';
    }
}