<?php
namespace Frizus\Module\Validation;

use Frizus\Module\Helper\MessageBag;
use Frizus\Module\Request\Base\Request;
use Frizus\Module\Rule\Base\Rule;
use Frizus\Module\Validation\Traits\FormatsMessages;
use Frizus\Module\Validation\Traits\MimeToExtension;
use Frizus\Module\Validation\Traits\ValidateAttributes;

class Validator
{
    use ValidateAttributes;
    use FormatsMessages;
    use MimeToExtension;

    public $data;

    public $rules;

    public $innerRulesParameters;

    /**
     * @var array
     */
    public $processing = [];

    protected $numericRules = [
        'integer',
        'integerId',
    ];

    /**
     * @var array
     */
    protected $extra = [];

    public $skipNotRequiredOrEmpty = [];

    public $customMessages = [
        ':required' => 'Заполните :attribute.',
        ':notemptystring' => ':attribute должен быть не пустой строкой.',
        ':csrf' => 'Отправьте форму заново',
        ':file' => 'Файл не загружен',
        ':extension' => 'Файл должен быть правильного расширения',
    ];

    /**
     * @var Request|null
     */
    protected $request;

    /**
     * @var MessageBag
     */
    protected $messages;

    protected $stopOnFirstFailure = false;

    protected $bailAttributes;

    public function __construct($data, $rules, $messages = [], $request = null)
    {
        $this->data = $data;
        $this->customMessages = array_merge($this->customMessages, $messages);
        $this->request = $request;
        $this->initRules($rules);
    }

    public function initRules($rules)
    {
        $this->bailAttributes = [];
        $this->innerRulesParameters = [];
        foreach ($rules as $attribute => &$rules2) {
            if (!is_array($rules2)) {
                $rules2 = [$rules2];
            }

            foreach ($rules2 as $i => $rule) {
                if ($rule === 'bail') {
                    unset($rules[$attribute][$i]);

                    if (!array_key_exists($attribute, $this->bailAttributes)) {
                        $this->bailAttributes[$attribute] = true;
                    }
                } elseif (($rule === '') ||
                    (
                        is_string($rule) &&
                        (strpos($rule, ':') === 0)
                    )
                ) {
                    unset($rules[$attribute][$i]);
                } elseif (is_string($rule)) {
                    $explodedRule = explode(':', $rule);

                    if (count($explodedRule) > 1) {
                        $rule = array_shift($explodedRule);
                        $rules[$attribute][$i] = $rule;
                        $this->innerRulesParameters[$attribute][$i] = $explodedRule;
                    }
                }
            }

            if (empty($rules2)) {
                unset($rules[$attribute]);
            }
        }
        $this->rules = $rules;
    }

    public function validate()
    {
        if (!$this->passes()) {
            throw new ValidationException($this);
        }

        return $this->data;
    }

    public function passes()
    {
        $this->messages = new MessageBag();
        $this->skipNotRequired = [];

        foreach ($this->rules as $attribute => $rules) {
            if ($this->stopOnFirstFailure && $this->messages->isNotEmpty()) {
                return false;
            }

            foreach ($rules as $ruleIndex => $rule) {
                $this->validateAttribute($attribute, $rule, $ruleIndex);
                if ($this->shouldStopValidating($attribute)) {
                    break;
                }
            }
        }

        return $this->messages->isEmpty();
    }

    public function fails()
    {
        return !$this->passes();
    }

    protected function shouldStopValidating($attribute)
    {
        return array_key_exists($attribute, $this->skipNotRequiredOrEmpty) ||
            (
                array_key_exists($attribute, $this->bailAttributes) &&
                $this->messages->has($attribute)
            );
    }

    public function validateAttribute($attribute, $rule, $ruleIndex)
    {
        $keyExists = array_key_exists($attribute, $this->data);
        $value = $keyExists ? $this->data[$attribute] : null;

        if (is_string($rule)) {
            $method = "validate" . str_replace('_', '', $rule);
            $baseRule = method_exists($this, $method);
        } else {
            $baseRule = false;
        }

        if (!$baseRule && is_callable($rule)) {
            $fail = false;
            $rule($attribute, $value, $keyExists, function($message) use ($attribute, $fail) {
                if (!$fail) {
                    $fail = true;
                }
                $this->messages->add(
                    $attribute,
                    $this->makeReplacements($message, $attribute)
                );
            });
            return !$fail;
        }

        if ($rule instanceof Rule) {
            return $this->validateUsingCustomRule($attribute, $value, $keyExists, $rule);
        }

        $args = [$attribute, $value, $keyExists];
        if (isset($this->innerRulesParameters[$attribute][$ruleIndex])) {
            $args[] = $this->innerRulesParameters[$attribute][$ruleIndex];
        } else {
            $args[] = [];
        }

        if (is_string($rule) && !$this->$method(...$args)) {
            $this->messages->add(
                $attribute,
                $this->makeReplacements($this->getMessage($attribute, $rule), $attribute)
            );
            return false;
        }

        return true;
    }

    protected function validateUsingCustomRule($attribute, $value, $keyExists, $rule)
    {
        if (method_exists($rule, 'setValidator')) {
            $rule->setValidator($this);
        }

        if (method_exists($rule, 'setData')) {
            $rule->setData($this->data);
        }

        if (!$rule->passes($attribute, $value, $keyExists)) {
            $this->messages->add(
                $attribute,
                $this->makeReplacements($rule->message(), $attribute)
            );
            return false;
        }

        return true;
    }

    public function stopOnFirstFailure($stopOnFirstFailure = true)
    {
        $this->stopOnFirstFailure = $stopOnFirstFailure;
        return $this;
    }

    public function messages()
    {
        if (!$this->messages) {
            $this->passes();
        }

        return $this->messages;
    }

    /**
     * @return Request|null
     */
    public function request()
    {
        return $this->request;
    }

    public function setData($key, $value = null)
    {
        $n = func_num_args();

        if ($n === 1) {
            if (is_array($key)) {
                $this->data = $key;
            }
        }

        $this->data[$key] = $value;
    }

    public function processing($key = null, $value = null)
    {
        $n = func_num_args();

        if ($n === 0) {
            return $this->processing;
        }

        if ($n === 1) {
            return $this->processing[$key] ?? null;
        }

        $this->processing[$key] = $value;
    }

    public function extra($key = null, $value = null, $default = null)
    {
        $n = func_num_args();

        if ($n === 0) {
            return $this->extra;
        }

        if ($n === 1) {
            return $this->extra[$key] ?? $default;
        }

        $this->extra[$key] = $value;

        if (isset($this->request)) {
            $this->request->extra($key, $value, $default);
        }
    }

    public function hasRule($attribute, $desiredRules)
    {
        if (!array_key_exists($attribute, $this->rules)) {
            return false;
        }

        foreach ($this->rules[$attribute] as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            foreach ($desiredRules as $desiredRule) {
                if (strcasecmp($rule, $desiredRule) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}