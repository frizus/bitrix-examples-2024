<?php
// пример переопределенного класса. не используется
return;
// @see https://aclips.ru/bitrix-override-classes/
/**
 * Перегрузка классов
 */

$classDirectoryPath = __DIR__ . '/override';

/**
 * Конфигуратор переопределяемых классов
 */
$config = [
//    "Bitrix\\Sale\\Internals\\OrderPropsValueTable" => [
//        'classPath' => $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/lib/internals/orderprops_value.php',
//        'overrideClass' => "Sale\\Internals\\OverrideOrderPropsValueTable",
//        'overrideClassPath' => 'overrideorderpropsvaluetable.php',
//    ]
];

/**
 * Регистрация автозагрузчика
 * @see \Bitrix\Main\Loader::autoLoad
 */
spl_autoload_register(function ($className) use ($config, $classDirectoryPath) {
    $className = ltrim($className, "\\");

    if (empty($config[$className])) {
        return;
    }


    $classParts = explode('\\', $className);
    $baseClassName = array_pop($classParts);
    $namespace = implode('\\', array_filter($classParts));

    $virtualClassName = "___Virtual{$baseClassName}";
    $classFilePath = $classDirectoryPath . '/' . $config[$className]['overrideClassPath'];
    if (!file_exists($config[$className]['classPath']) ||
        !file_exists($classFilePath)
    ) {
        return;
    }

    $classContent = file_get_contents($config[$className]['classPath']);
    $classContent = preg_replace('#^<\?(?:php)?\s*#', '', $classContent);
    $classContent = str_replace("class {$baseClassName}", "class {$virtualClassName}", $classContent);

    if(!empty($config[$className]['replace'])) {
        foreach ($config[$className]['replace'] as $from => $to) {
            $classContent = str_replace($from, $to, $classContent);
        }
    }

    eval($classContent);

    $overrideClassParts = explode('\\', $config[$className]['overrideClass']);
    $overrideBaseClassName = array_pop($overrideClassParts);
    $overrideClassContent = file_get_contents($classFilePath);
    $overrideClassContent = str_replace("class {$overrideBaseClassName}", "class {$baseClassName}", $overrideClassContent);
    $overrideClassContent = preg_replace('#^<\?(?:php)?\s*#', '', $overrideClassContent);
    $overrideClassContent = preg_replace('#extends ([^\s]+)#', "extends {$virtualClassName}", $overrideClassContent);

    $overrideClassContent = preg_replace('#namespace ([^\s]+);#',
        $namespace ? "namespace {$namespace};" : "",
        $overrideClassContent);

    eval($overrideClassContent);

    return;
}, true, true);