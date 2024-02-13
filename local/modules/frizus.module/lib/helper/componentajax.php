<?
namespace Frizus\Module\Helper;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;use Bitrix\Main\Security\Sign\Signer;

class ComponentAjax
{
    public static function ajax($componentName)
    {
        $signer = new \Bitrix\Main\Security\Sign\Signer;
        $request = [];
        $salt = self::paramsSalt($componentName);
        foreach (['signedParamsString', 'siteId', 'siteTemplateId', 'template'] as $key) {
            if (isset($_REQUEST[$key]) && is_string($_REQUEST[$key]) && $_REQUEST[$key]) {
                try {
                    $request[$key] = $signer->unsign($_REQUEST[$key], $salt);
                    $request[$key] = unserialize(base64_decode($request[$key]), ['allowed_classes' => false]);
                    if ($request[$key] === false) {
                        throw new \Exception;
                    }
                } catch (\Bitrix\Main\Security\Sign\BadSignatureException | \Exception $e) {
                    die('Bad signature.');
                }
            } else {
                die('Missing/wrong param.');
            }
        }

        define('SITE_ID', $request['siteId']);
        define('SITE_TEMPLATE_ID', $request['site_template_id']);
        $request['signedParamsString']['SUBMIT'] = 'Y';

        global $APPLICATION;

        $APPLICATION->IncludeComponent(
            $componentName,
            $request['template'],
            $request['signedParamsString']
        );
    }

    public static function componentNameFromFolder($dir)
    {
        return ComponentHelper::componentNameFromFolder($dir);
    }

    public static function pluginNameFromComponentName($componentName, $templateName = null)
    {
        $string = $componentName;
        if (!ComponentHelper::isDefaultTemplate($templateName)) {
            $string .= ' ' . $templateName;
        }

        return lcfirst(str_replace([':', '.', '_', '-', ' '], '', ucwords($string, ':._- ')));
    }

    /**
     * @param \CBitrixComponent $component
     */
    public static function plugin($plugin, $component, $arParams, $selector = null, $pluginParams = true, $withDefaultParams = true)
    {
        if (!$plugin) {
            if (isset($component)) {
                $plugin = self::pluginNameFromComponentName($component->getName(), $component->getTemplateName());
            } else {
                return;
            }
        }
        $sign = new Signer();
        if ($pluginParams === true) {
            $pluginParams = [];
            $withDefaultParams = true;
        }
        if ($withDefaultParams && isset($component) && isset($arParams)) {
            $salt = str_replace(':', '.', $component->getName());
            $extraParams = [
                'signedParamsString' => $sign->sign(base64_encode(serialize($arParams)), $salt),
                'siteId' => $sign->sign(base64_encode(serialize($component->getSiteId())), $salt),
                'siteTemplateId' => $sign->sign(base64_encode(serialize($component->getSiteTemplateId())), $salt),
                'ajaxUrl' => $component->getPath() . '/ajax.php',
                'template' => $sign->sign(base64_encode(serialize($component->getTemplate()->GetName())), $salt),
            ];
            $pluginParams = array_merge($extraParams, $pluginParams);
        }
        if (!is_string($selector)) {
            $selector = 'body';
        }
        echo "
        <script>
        (function($, window, document) {
            $(document).ready(function() {
                $('{$selector}').{$plugin}(" . (is_array($pluginParams) ? json_encode($pluginParams) : '') . ");
            })
        })(jQuery, window, document);
        </script>";
    }

    public static function extraJs($object, $path = null, $extra = [])
    {
        $filename = ComponentHelper::templatePageWithoutExtension($object);
        $template = ComponentHelper::getTemplate($object);

        if ($filename === 'template') {
            return;
        }

        if (!$path) {
            $path = $template->GetFolder() . '/script/' . $filename . '.js';
        }

        /*if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            return;
        }*/

        foreach ([$path, ...$extra] as $script) {
            echo '<script src="' .  \CUtil::GetAdditionalFileURL($script) . '"></script>';
        }
    }

    public static function paramsSalt($componentName)
    {
        return str_replace(':', '.', $componentName);
    }
}