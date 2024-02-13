<?php
namespace Frizus\Module\Request;

use Frizus\Module\Request\Base\AjaxRequest;
use Frizus\Module\Request\Traits\CheckAuthorization;
use Frizus\Module\Rule\EmailRule;
use Frizus\Module\Rule\EmptyStringRule;

class LoadPopupRequest extends AjaxRequest
{
    use CheckAuthorization;

    public $mustBeGuest;

    public function __construct($mustBeGuest = null, $step = null)
    {
        $this->mustBeGuest = $mustBeGuest;
        parent::__construct(null, $step);
    }

    public function validationData()
    {
        return $this->requestData(null, ['needPopup']);
    }
}