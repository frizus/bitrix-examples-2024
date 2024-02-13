<?
namespace Frizus\Module\HttpRequests\HttpClientWrapper;

use Bitrix\Main\Web\HttpClient as BitrixHttpClient;

trait HttpClientBaseTrait
{
    /**
     * @var BitrixHttpClient
     */
    public $response;

    // https://mrcappuccino.ru/blog/post/work-with-http-bitrix-d7
    public $defaultOptions = [
        'redirect' => true, // true, если нужно выполнять редиректы
        'redirectMax' => 5, // Максимальное количество редиректов
        'waitResponse' => true, // true - ждать ответа, false - отключаться после запроса
        'socketTimeout' => 30, // Таймаут соединения, сек
        'streamTimeout' => 60, // Таймаут чтения ответа, сек, 0 - без таймаута
        'version' => BitrixHttpClient::HTTP_1_0, // версия HTTP (HttpClient::HTTP_1_0 или HttpClient::HTTP_1_1)
        'proxyHost' => '', // адрес
        'proxyPort' => '', // порт
        'proxyUser' => '', // имя
        'proxyPassword' => '', // пароль
        'compress' => false, // true - принимать gzip (Accept-Encoding: gzip)
        'charset' => 'UTF-8', // Кодировка тела для POST и PUT
        'disableSslVerification' => false, // true - отключить проверку ssl (с 15.5.9)
    ];

    public function send($method, $url, $options = [], $query = [], $post = [], $attach = [], $cookies = [], $headers = [])
    {
        if (!empty($query)) {
            $url = $this->buildUrl($url, $query);
        }

        if (!isset($this->response)) {
            $this->response = new BitrixHttpClient($options);
        }

        if (!empty($headers)) {
            $this->response->clearHeaders();
            $this->response->setHeaders($headers);
        }

        $data = !empty($post) ? $post : null;

        return $this->response->query($method, $url, $data);
    }

    public function httpClientErrors()
    {
        $message = '';
        $error = $this->response->getError();
        if (!empty($error)) {
            $first = true;
            foreach ($error as $code => $val) {
                if (!$first) $message .= "\n";
                else $first = false;
                $message .= '[' . $code . '] ' . $val;
            }
        }
        return $message;
    }

    protected function buildUrl($url, $query)
    {
        if (!empty($query)) {
            $url = strtok($url, '?') . '?' . http_build_query($query);
        }
        return $url;
    }

    protected function mergeOptions($options, $defaultOptions)
    {
        $newOptions = [];

        foreach ($options as $key => $value) {
            if (array_key_exists($key, $defaultOptions)) {
                if (is_array($value) && is_array($defaultOptions[$key])) {
                    $newOptions[$key] = $this->mergeOptions($options[$key], $defaultOptions[$key]);
                } else {
                    $newOptions[$key] = $value;
                }
                unset($defaultOptions[$key]);
            } else {
                $newOptions[$key] = $value;
            }
        }

        if (!empty($defaultOptions)) {
            foreach($defaultOptions as $key => $value) {
                $newOptions[$key] = $value;
                unset($defaultOptions[$key]);
            }
        }

        return $newOptions;
    }
}