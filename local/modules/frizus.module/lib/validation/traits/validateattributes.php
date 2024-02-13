<?php
namespace Frizus\Module\Validation\Traits;

use Frizus\Module\Request\Helper\UploadedFile;
use Frizus\Module\Request\Helper\UploadedFiles;
use Frizus\Helpers\Arr;

trait ValidateAttributes
{
    public function validateList($attribute, $value, $keyExists, $parameters)
    {
        return is_array($parameters) &&
            !empty($parameters) &&
            in_array($value, $parameters, true);
    }

    public function validateArray($attribute, $value, $keyExists)
    {
        return is_array($value);
    }

    public function validateNotEmptyArray($attribute, $value, $keyExists)
    {
        return is_array($value) && !empty($value);
    }

    public function validateInteger($attribute, $value, $keyExists)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public function validatePositiveFloat($attribute, $value, $keyExists)
    {
        return (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) &&
            (floatval($value) >= 0);
    }

    public function validateIntegerId($attribute, $value, $keyExists)
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) &&
            (intval($value) > 0);
    }

    public function validatePositiveInteger($attribute, $value, $keyExists)
    {
        return (filter_var($value, FILTER_VALIDATE_INT) !== false) &&
            (intval($value) >= 0);
    }

    public function validateRequired($attribute, $value, $keyExists)
    {
        return $keyExists;
    }

    public function validateNotRequired($attribute, $value, $keyExists)
    {
        if (!$keyExists) {
            $this->skipNotRequiredOrEmpty[$attribute] = true;
        }

        return true;
    }

    public function validateNotRequiredOrEmpty($attribute, $value, $keyExists)
    {
        if (
            !$keyExists ||
            (is_array($value) && empty($value)) ||
            (!is_array($value) && (strval($value) === ''))
        ) {
            $this->skipNotRequiredOrEmpty[$attribute] = true;
        }

        return true;
    }

    public function validateIsString($attribute, $value, $keyExists)
    {
        return is_string($value);
    }

    public function validateNotEmptyString($attribute, $value, $keyExists)
    {
        return is_string($value) && ($value !== '');
    }

    public function validateCsrf($attribute, $value, $keyExists)
    {
        $sessid = bitrix_sessid();
        if (!
        (
            $keyExists &&
            ($value !== '') &&
            ($value === $sessid)
            )
        ) {
            $this->extra('csrf', bitrix_sessid());
            return false;
        }

        return true;
    }

    public function validateInt1($attribute, $value, $keyExists)
    {
        return $value === '1';
    }

    public function validateTrim($attribute, $value, $keyExists)
    {
        if (is_string($value)) {
            $this->data[$attribute] = trim($value);
        }

        return true;
    }

    public function validateMax($attribute, $value, $keyExists, $parameters)
    {
        if (isset($parameters[0])) {
            $max = doubleval(trim($parameters[0]));
        } else {
            $max = 0;
        }

        foreach (Arr::wrap($this->getSize($attribute, $value)) as $singleSize) {
            if ($singleSize > $max) {
                return false;
            }
        }

        return true;
    }

    public function validateFile($attribute, $value, $keyExists, $parameters)
    {
        $multiple = $parameters[0] === 'true';

        if (filter_var($parameters[0], FILTER_VALIDATE_INT) !== false) {
            $count = intval($parameters[0]);
            if ($count > 1) {
                $multiple = true;
            }
        }

        if ($multiple) {
            if (!($value instanceof UploadedFiles) ||
                !$value->uploaded() ||
                (isset($count) && ($count < $value->count()))
            ) {
                return false;
            }

            return true;
        }

        if (!($value instanceof UploadedFile)) {
            return false;
        }

        return $value->uploaded();
    }

    public function validateExtension($attribute, $value, $keyExists, $parameters)
    {
        $extensions = preg_split('#\s*,\s*#', strval($parameters[0]), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($extensions)) {
            return true;
        }

        $extensions = array_map('mb_strtolower', $extensions);
        $extensions = array_map(function($value) { return $value === 'jpeg' ? 'jpg' : $value; }, $extensions);

        if (!
            (
                $value instanceof UploadedFiles ||
                $value instanceof UploadedFile
            )
        ) {
            return false;
        }

        if ($value instanceof UploadedFiles) {
            $mimes = [];
            foreach ($value->files as $cursor => $file) {
                $mimes[$cursor] = @mime_content_type($file->tmpName);
            }
        } else {
            $mimes = [@mime_content_type($value->tmpName)];
        }

        $mimeExtensions = [];
        $passes = true;

        foreach ($mimes as $cursor => $mime) {
            if ($mime === false) {
                if ($passes) {
                    $passes = false;
                }
                $mimeExtensions[$cursor] = null;
                continue;
            }

            $extension = $this->mimeToExtension[$mime] ?? null;

            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $mimeExtensions[$cursor] = $extension;

            if ($passes && !in_array($extension, $extensions, true)) {
                $passes = false;
            }
        }

        $this->processing($attribute . '-mime', $value instanceof UploadedFile ? reset($mimes) : $mimes);
        $this->processing($attribute . '-extension', $value instanceof UploadedFile ? reset($mimeExtensions) : $mimeExtensions);

        return $passes;
    }

    protected function getSize($attribute, $value)
    {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        if (is_array($value)) {
            return count($value);
        } elseif ($value instanceof UploadedFiles) {
            return array_map(function($elem) { return $elem / 1024; }, $value->getSizes());
        } elseif ($value instanceof UploadedFile) {
            return $value->getSize() / 1024;
        } elseif ($hasNumeric) {
            if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                return intval($value);
            }

            return doubleval($value);
        }

        return mb_strlen($value ?? '');
    }
}