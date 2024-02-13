<?
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
use Frizus\Module\Helper\ComponentAjax;
ComponentAjax::ajax(ComponentAjax::componentNameFromFolder(__DIR__));