<?php
namespace Frizus\Module\HttpRequests;

use Frizus\Module\HttpRequests\Base;
use Frizus\Module\HttpRequests\HttpClientWrapper\HttpClientWrapper;

class RecaptchaRequest extends Base
{
    public function __construct()
    {
        $this->httpClient = new HttpClientWrapper([
            'getBodyStatus' => [200],
            'validateBody' => function (HttpClientWrapper $httpClient) {
                if ($httpClient->validateBody()) {
                    if ($httpClient->status === 200) {
                        $httpClient->validateJson(
                            [
                                'success',
                            ],
                            [
                                'action' => 'string',
                                'score' => 'float',
                            ],
                            true
                        );
                    }
                }
            },
            'makeResult' => function(HttpClientWrapper $httpClient) {
                if ($httpClient->status === 200) {
                    return $httpClient->result;
                }

                return false;
            },
            'options' => [
                'waitResponse' => true,
                'socketTimeout' => 60,
                'streamTimeout' => 1200,
            ],
        ]);
    }

    /**
     * @see https://stackoverflow.com/a/30600026
     */
    public function request($secret, $gRecaptchaResponse)
    {
        if ($this->httpClient->request(
            'POST',
            'https://www.google.com/recaptcha/api/siteverify',
            [],
            [
                'secret' => $secret,
                'response' => $gRecaptchaResponse,
            ]
        )) {
            return is_array($this->httpClient->result);
        }

        return false;
    }
}
