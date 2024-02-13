<?php
namespace Frizus\Module\Validation;

class ValidationException extends \Exception
{
    /**
     * @var Validator
     */
    protected $validator;

    public function __construct($validator, $message = "", $code = 0, \Throwable $previous = null)
    {
        $this->validator = $validator;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return \Frizus\Module\Helper\MessageBag
     */
    public function getMessageBag()
    {
        return $this->validator->messages();
    }
}