<?php
namespace Frizus\Module\Request\Base;

use Bitrix\Main\Application;
use Frizus\Module\Request\Helper\UploadedFile;
use Frizus\Module\Request\Helper\UploadedFiles;
use Frizus\Module\Request\Traits\InputToProperties;
use Frizus\Module\Validation\ValidationException;
use Frizus\Module\Validation\Validator;
use Frizus\Helpers\Arr;

abstract class Request
{
    use InputToProperties;

    /**
     * @var array
     */
    public $processing = [];

    /**
     * @var array
     */
    protected $extra = [];

    /**
     * @var Validator
     */
    public $validator;

    protected $data;

    protected $step;

    protected $validateCalledOnce = false;

    protected $lastCalledStep;

    protected $skipRules;

    public function __construct($step = null)
    {
        if (isset($step)) {
            $this->step = $step;
        }
    }

    public function step($step)
    {
        $this->step = $step;
        return $this;
    }

    public function validationData()
    {
        return [];
    }

    public function rules()
    {
        return [];
    }

    protected function messages()
    {
        return [];
    }

    public function input($key = null, $value = null)
    {
        $n = func_num_args();

        if ($n === 0) {
            return $this->data;
        }

        if ($n === 1) {
            return $this->data[$key] ?? null;
        }

        if (!isset($value)) {
            if (array_key_exists($key, $this->data)) {
                unset($this->data[$key]);
            }
        } else {
            $this->data[$key] = $value;
        }
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

        if (!isset($value)) {
            if (array_key_exists($key, $this->processing)) {
                unset($this->processing[$key]);
            }
        } else {
            $this->processing[$key] = $value;
        }
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
    }

    protected function isJson()
    {
        $contentType = Application::getInstance()->getContext()->getRequest()->getHeader('content-type');

        if (isset($contentType)) {
            return strpos($contentType, '/json') !== false ||
                strpos($contentType, '+json') !== false;
        }

        return false;
    }

    protected function requestData($queryKeys = [], $postKeys = [], $postPrefix = null, $excludeFromGroup = null, $fileKeys = [])
    {
        $data = [];

        if (is_array($queryKeys) && !empty($queryKeys)) {
            $list = $this->getQuery();
            foreach ($queryKeys as $key) {
                if (($value = Arr::get($key, $list)) !== null) {
                    $data[$key] = $value;
                }
            }
        }

        if (is_array($postKeys) && !empty($postKeys)) {
            $list = $this->getPost();

            foreach ($postKeys as $key) {
                if (($value = Arr::get($this->getNeedleKey($key, $postPrefix, $excludeFromGroup), $list)) !== null) {
                    $data[$key] = $value;
                }
            }
        }

        if (is_array($fileKeys) && !empty($fileKeys)) {
            $list = $this->getFiles();

            foreach ($fileKeys as $key) {
                if (($datum = $this->getFileData($this->getNeedleKey($key, $postPrefix, $excludeFromGroup), $list)) !== null) {
                    $data[$key] = $datum;
                }
            }
        }

        return $data;
    }

    protected function getFileData($needleKey, $list)
    {
        $needleKey = explode('.', $needleKey);
        $needleKeyLength = count($needleKey);
        $firstKey = array_shift($needleKey);

        if (!array_key_exists($firstKey, $list) ||
            !is_array($list[$firstKey]) ||
            (count($list[$firstKey]) !== 5)
        ) {
            return null;
        }

        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
            if (!array_key_exists($field, $list[$firstKey])) {
                return null;
            }
        }

        $list = $list[$firstKey];

        $correct = 0;
        $data = [];
        $multiple = null;
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
            if ($needleKeyLength > 1) {
                if (($value = Arr::get($needleKey, $list[$field])) === null) {
                    return null;
                }
            } else {
                $value = $list[$field];
            }

            if ($this->isCorrectFileStructure($value)) {
                if (!isset($multiple)) {
                    $multiple = is_array($value);
                }

                if ($multiple) {
                    foreach ($value as $i => $singleValue) {
                        $data[$i][$field] = $singleValue;
                    }
                } else {
                    $data[$field] = $value;
                }

                $correct++;
            }
        }

        if ($correct !== 5) {
            return null;
        }

        if ($multiple) {
            return new UploadedFiles($data);
        }

        return new UploadedFile(...array_values($data));
    }

    protected function isCorrectFileStructure($value)
    {
        if (!isset($value)) {
            return false;
        }

        if (is_scalar($value)) {
            return true;
        }

        if (!is_array($value) || Arr::isAssoc($value)) {
            return false;
        }

        foreach ($value as $singleValue) {
            if (!isset($singleValue) || !is_scalar($singleValue)) {
                return false;
            }
        }

        return true;
    }

    protected function getQuery()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        return $request->getQueryList()->toArray();
    }

    protected function getPost()
    {
        $request = Application::getInstance()->getContext()->getRequest();

        if ($this->isJson()) {
            return $request->getJsonList()->toArray();
        }

        return $request->getPostList()->toArray();
    }

    protected function getFiles()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        return $request->getFileList()->toArray();
    }

    protected function getNeedleKey($key, $postPrefix, $excludeFromGroup)
    {
        $needleKey = null;
        if (isset($postPrefix) && $postPrefix) {
            if (isset($excludeFromGroup)) {
                if (!in_array($key, $excludeFromGroup, true)) {
                    $needleKey = $postPrefix . '.' . $key;
                }
            }
        }

        return $needleKey ?? $key;
    }

    protected function getValidatorInstance()
    {
        if (isset($this->validator)) {
            if ($this->validateCalledOnce && $this->lastCalledStep !== $this->step) {
                $this->validator->initRules(!$this->skipRules ? $this->rules() : []);
            }
            return $this->validator;
        }

        $this->validator = new Validator($this->data, !$this->skipRules ? $this->rules() : [], $this->messages(), $this);
        return $this->validator;
    }

    protected function prepareForValidation()
    {
        $this->data = $this->validationData();
    }

    public function validate($step = null)
    {
        if (isset($step)) {
            $this->step($step);
        }

        if (!$this->validateCalledOnce) {
            $this->prepareForValidation();
        }

        $validator = $this->getValidatorInstance();

        if (!$this->validateCalledOnce) {
            $this->validateCalledOnce = true;
        }

        if ($this->lastCalledStep !== $this->step) {
            $this->lastCalledStep = $this->step;
        }

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }

        $this->passedValidation();
        return $this;
    }

    protected function passedValidation()
    {

    }

    protected function failedValidation(Validator $validator)
    {
        throw new ValidationException($validator);
    }
}