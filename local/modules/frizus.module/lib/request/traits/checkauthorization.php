<?
namespace Frizus\Module\Request\Traits;

trait CheckAuthorization
{
    protected $skipRules;

    protected function prepareForValidation()
    {
        parent::prepareForValidation();
        $this->checkAuthorization();
    }

    protected function checkAuthorization()
    {
        if (isset($this->mustBeGuest)) {
            global $USER;
            if ($this->mustBeGuest === $USER->IsAuthorized()) {
                $this->skipRules = true;
                $validator = $this->getValidatorInstance();
                $validator->messages()->add('common', $this->mustBeGuest ? 'Вы уже авторизованы' : 'Вы не авторизованы');
                $this->failedValidation($validator);
            }
        }
    }
}