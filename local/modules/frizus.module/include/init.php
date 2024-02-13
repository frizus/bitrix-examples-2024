<?
use Bitrix\Main\Loader;

$modules = [
    'iblock',
    'catalog',
    'sale',
    'highloadblock',
];

foreach ($modules as $i => $module) {
    if (Loader::includeModule($module)) {
        unset($modules[$i]);
    }
}

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
if (!$request->isAdminSection() && $modules) {
    die((count($modules) === 1 ? 'Установите модуль' : 'Установите модули') . ': ' . implode(', ', $modules));
}

include __DIR__ . '/helpers.php';
//include __DIR__ . '/events.php';