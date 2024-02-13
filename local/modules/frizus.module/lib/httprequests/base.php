<?php
namespace Frizus\Module\HttpRequests;

use Frizus\Module\HttpRequests\HttpClientWrapper\HttpClientWrapper;

class Base
{
    /**
     * @var HttpClientWrapper
     */
    public $httpClient;

    public function getError()
    {
        return is_array($this->httpClient->result) && isset($this->httpClient->result['error']) ?
            $this->httpClient->result['error'] :
            (isset($this->httpClient->error) ?
                $this->httpClient->error :
                'Неизвестная ошибка запроса');
    }

    public function getResult()
    {
        return $this->httpClient->result;
    }
}