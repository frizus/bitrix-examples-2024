<?
namespace Frizus\Module\Request\Traits;

use Frizus\Module\Request\Helper\UploadedFile;
use Frizus\Module\Request\Helper\UploadedFiles;
use Frizus\Helpers\Arr;

trait InputToProperties
{
    /** @var \Bitrix\Iblock\ORM\EO_ElementV2 $element */
    public function toProperties($keys = null, $oldFileValues = [], $element = null)
    {
        if (!isset($keys)) {
            $input = $this->input();
            $keys = array_keys($input);
            $upperKeys = $keys;
            $upperKeys = array_map('mb_strtoupper', $upperKeys);
            $keys = array_combine($upperKeys, $keys);
            unset($upperKeys);
        } else {
            $input = [];
            if (!Arr::isAssoc($keys)) {
                $indexedKeys = $keys;
                $keys = [];
                foreach ($indexedKeys as $key) {
                    $keys[mb_strtoupper($key)] = $key;
                }
                unset($indexedKeys);
            }
            foreach ($keys as $key) {
                $input[$key] = $this->input($key);
            }
        }
        $keys = array_flip($keys);

        $propertyValues = [];
        foreach ($input as $key => $field) {
            $code = $keys[$key];

            if (array_key_exists($key, $oldFileValues)) {
                $propertyValues[$code] = [];
                foreach ($oldFileValues[$key] as $oldFileValue) {
                    $propertyValues[$code][$oldFileValue] = $oldFileValue;
                }
                if (isset($element)) {
                    foreach ($element[$code] as $file) {
                        $found = false;
                        foreach ($oldFileValues[$key] as $oldFileValue) {
                            if ((int)$oldFileValue === (int)$file['ID']) {
                                $found = true;
                                break;
                            }
                        }

                        if (!$found) {
                            $propertyValues[$code][$file['ID']] = ['del' => 'Y'];
                        }
                    }
                }
            }

            if ($field instanceof UploadedFiles) {
                if (!array_key_exists($key, $oldFileValues)) {
                    $propertyValues[$code] = [];
                }
                $i = 0;
                foreach ($field->files as $file) {
                    $propertyValues[$code]['n' . $i++] = ['VALUE' => $file->getFileArray()];
                }
            } elseif ($field instanceof UploadedFile) {
                if (!array_key_exists($key, $oldFileValues)) {
                    $propertyValues[$code] = [];
                }
                $propertyValues[$code] = [
                    'n0' => ['VALUE' => $field->getFileArray()],
                ];
            } elseif (!array_key_exists($key, $oldFileValues)) {
                if (is_array($field)) {
                    $propertyValues[$code] = [];
                    $i = 0;
                    foreach ($field as $singleValue) {
                        $propertyValues[$code]['n' . $i++] = ['VALUE' => $singleValue];
                    }
                } elseif (!$field) {
                    $propertyValues[$code] = [
                        'n0' => ['VALUE' => false],
                    ];
                } else {
                    $propertyValues[$code] = [
                        'n0' => ['VALUE' => $field],
                    ];
                }
            }
        }

        return $propertyValues;
    }
}