<?
namespace Frizus\Module\HttpRequests\HttpClientWrapper;

class HttpClientWrapper
{
    use HttpClientBaseTrait;

    public $getBodyStatus;

    public $getBodyNotStatus;

    public $statusTypes;

    public $options;

    public $cookies;

    public $validateBodyClosure;

    public $makeResultClosure;

    public $noSystemError;

    public $url;

    public $query;

    public $result;

    public $status;

    public $statusType;

    public $errorType;

    public $error;

    public $hideUrl;

    public function __construct($options)
    {
        if (!is_array($options)) {
            return;
        }
        if (isset($options['getBodyStatus'])) {
            $this->getBodyStatus = (array)$options['getBodyStatus'];
        }
        if (isset($options['getBodyNotStatus'])) {
            $this->getBodyNotStatus = (array)$options['getBodyNotStatus'];
        }
        if (isset($options['statuses'])) {
            $this->statusTypes = $options['statuses'];
        }
        if (isset($options['validateBody'])) {
            $this->validateBodyClosure = $options['validateBody'];
        }
        if (isset($options['makeResult'])) {
            $this->makeResultClosure = $options['makeResult'];
        }
        if (isset($options['noSystemError'])) {
            $this->noSystemError = $options['noSystemError'];
        }
        if (isset($options['hideUrl'])) {
            $this->hideUrl = $options['hideUrl'];
        }
        $this->options = $this->mergeOptions($options['options'] ?? [], $this->defaultOptions);
        $this->cookies = $options['cookies'] ?? [];
    }

    public function request($method, $url, $query = [], $post = [], $attach = [], $headers = [])
    {
        $this->result = null;
        $this->error = null;
        $this->errorType = null;
        $this->status = null;
        $this->query = $query;
        $this->url = $this->buildUrl($url, $query);
        try {
            $result = $this->send($method, $url, $this->options, $query, $post, [], [], $headers);
            $this->url = $this->response->getEffectiveUrl();
            if (!$result) {
                if (!$this->noSystemError) {
                    $message = 'Ошибка подключения: ' . $this->httpClientErrors();
                } else {
                    $message = 'Ошибка подключения';
                }
                $this->addError($message, 'connection_exception');
                return false;
            }

            $this->status = $this->response->getStatus();
            if ($this->needToReadBody()) {
                $this->result = $this->response->getResult();
                if (!empty($this->response->getError())) {
                    if (!$this->noSystemError) {
                        $message = 'Ошибка подключения: ' . $this->httpClientErrors();
                    } else {
                        $message = 'Ошибка подключения';
                    }
                    $this->addError($message, 'connection_exception');
                    return false;
                }
            }
        } catch (\Throwable $e) {
            $this->url = $this->response->getEffectiveUrl();
            if (!$this->noSystemError) {
                $message = 'Ошибка подключения: ' . $e->getMessage();
            } else {
                $message = 'Ошибка подключения';
            }
            $this->addError($message, 'connection_exception_general');
            return false;
        }

        $this->statusType = $this->getStatusType();

        if (isset($this->result)) {
            if ($this->validateBodyClosure) {
                call_user_func($this->validateBodyClosure, $this);
            } else {
                $this->validateBody();
            }
            if ($this->hasError()) {
                if (!$this->noSystemError) {
                    $this->addResponseInfoToError();
                }
                $this->setErrorType('broken_response_body');
                return false;
            }
        }

        if ($this->makeResultClosure) {
            $this->result = call_user_func($this->makeResultClosure, $this);
        } else {
            $this->result = $this->makeResult();
        }
        if ($this->result === false || $this->hasError()) {
            if (!$this->noSystemError) {
                $this->addResponseInfoToError();
            } else {
                if (!$this->hasError()) {
                    $this->addError('Не успешный статус');
                }
            }
            $this->setErrorType('not_successful_status');
            return false;
        }

        return true;
    }

    public function makeResult()
    {
        if ($this->status === 200) {
            $this->result = [
                'status' => 'success',
            ];
            return;
        } elseif (isset($result['error'])) {
            $this->result = [
                'status' => 'error',
                'error' => $result['error'],
            ];
            return;
        }

        return false;
    }

    public function validateBody()
    {
        $this->result = $this->parseJson();
        $this->isNotJson();
        if ($this->hasError()) return false;

        if ($this->status >= 400) {
            $this->validateJsonError();
            if ($this->hasError()) return false;
        }
        return true;
    }

    public function isSuccess()
    {
        return !isset($this->error);
    }

    public function hasError()
    {
        return isset($this->error);
    }

    protected function parseJson()
    {
        $result = json_decode($this->result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $result = false;
        }

        return $result;
    }

    protected function getStatusType()
    {
        if (!is_null($this->statusTypes)) {
            foreach ($this->statusTypes as $statusType => $statuses) {
                if (in_array($this->status, $statuses, true)) {
                    return $statusType;
                }
            }
        }
    }

    protected function needToReadBody()
    {
        $success = true;

        if (!is_null($this->getBodyStatus)) {
            if (!in_array($this->status, $this->getBodyStatus, true)) {
                $success = false;
            }
        }

        if (!is_null($this->getBodyNotStatus)) {
            if (in_array($this->status, $this->getBodyNotStatus, true)) {
                $success = false;
            }
        }

        return $success;
    }

    protected function setErrorType($errorType)
    {
        $this->errorType = $errorType;
    }

    public function addError($message, $errorType = null)
    {
        $this->error = $message;
        if (isset($errorType)) {
            $this->setErrorType($errorType);
        }
    }

    protected function addResponseInfoToError()
    {
        if (isset($this->error)) {
            if ($this->error !== '') {
                $this->error .= "\n";
            }
        } else {
            $this->error = '';
        }
        $this->error .= 'HTTP-статус ' . $this->status;
        if (isset($this->statusType)) {
            $this->error .= ' (тип "' . $this->statusType . '")';
        }
        if (!$this->hideUrl) {
            $this->error .= ': ' . $this->response->getEffectiveUrl();
        }
    }

    public function isNotJson()
    {
        if ($this->result === false) {
            $this->addError('Пришел не JSON ответ');
        }
    }

    public function validateJsonError()
    {
        //$this->validateJson(['error'], false);
    }

    public function validateJson($required, $optional = [], $canHaveOtherFields = true)
    {
        foreach ($required as $key => $value) {
            if (filter_var($key, FILTER_VALIDATE_INT) !== false) {
                $required[$value] = 'any';
                unset($required[$key]);
            }
        }
        foreach ($optional as $key => $value) {
            if (filter_var($key, FILTER_VALIDATE_INT) !== false) {
                $optional[$value] = 'any';
                unset($optional[$key]);
            }
        }
        $data = [
            'invalidTypeRequired' => [],
            'invalidTypeOptional' => [],
            'missingRequired' => null,
            'anotherFieldsError' => false,
        ];
        $error = false;
        $haveAnotherFields = false;

        foreach ($this->result as $key => $value) {
            $success = false;
            if (array_key_exists($key, $required)) {
                $field = $required[$key];
                if ($field === 'array') {
                    $success = is_array($value);
                } elseif ($field === 'string') {
                    $success = is_string($value);
                } elseif ($field === 'float') {
                    $success = filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
                } else {
                    $success = true;
                }
                if (!$success) {
                    $data['invalidTypeRequired'][$key] = $field;
                    $error = true;
                }
                unset($required[$key]);
            } elseif (array_key_exists($key, $optional)) {
                $field = $optional[$key];
                if ($field === 'array') {
                    $success = is_array($value);
                } elseif ($field === 'string') {
                    $success = is_string($value);
                } elseif ($field === 'float') {
                    $success = filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
                } else {
                    $success = true;
                }
                if (!$success) {
                    $data['invalidTypeOptional'][$key] = $field;
                    $error = true;
                }
                unset($optional[$key]);
            } else {
                $haveAnotherFields = true;
            }
        }

        if (!$canHaveOtherFields && $haveAnotherFields) {
            $data['anotherFieldsError'] = true;
            $error = true;
        }
        $data['missingRequired'] = &$required;
        if (!empty($required)) {
            $error = true;
        }

        if ($error) {
            $errors = [];
            if (!empty($data['missingRequired'])) {
                $errors[] = str_replace(
                    ':fields',
                    implode(', ', array_keys($data['missingRequired'])),
                    (count($data['missingRequired']) === 1 ? 'В JSON-ответе отсутствует :fields' : 'В JSON-ответе отсутствуют :fields')
                );
            }
            if (!empty($data['invalidTypeRequired'])) {
                foreach ($data['invalidTypeRequired'] as $key => $value) {
                    $errors[] = 'тип поля ' . $key . ' должен быть ' . $value;
                }
            }
            if (!empty($data['invalidTypeOptional'])) {
                foreach ($data['invalidTypeOptional'] as $key => $value) {
                    $errors[] = 'тип опционального поля ' . $key . ' должен быть ' . $value;
                }
            }
            if ($data['anotherFieldsError']) {
                $errors[] = 'имеются избыточные поля';
            }
            $message = '';
            $l = count($errors) - 1;
            foreach ($errors as $i => $s) {
                if ($i === 0) {
                    $message .= mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
                } elseif ($i === $l) {
                    $message .= ' и ' . $s;
                } else {
                    $message .= ', ' . $s;
                }
            }
            $this->addError($message);
        }
    }
}