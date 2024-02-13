<?
namespace Frizus\Module\Helper;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;use Bitrix\Main\Security\Sign\Signer;

class ComponentHelper
{
    public const COMMON_FOLDER = '/local/templates/.default/include/';

    public const DEFAULT_EXTENSION = 'php';

    public static function getCommonPath($object, $fileName = null, $withDefaultExtension = true)
    {
        return $_SERVER['DOCUMENT_ROOT'] . self::getRelativeCommonPath($object, $fileName, $withDefaultExtension);
    }

    public static function getRelativeCommonPath($object, $fileName = null, $withDefaultExtension = true)
    {
        static $paths = [];
        $key = self::getRelativeCommonPathKey($object, $fileName, $withDefaultExtension);

        if (!key_exists($key, $paths)) {
            $path = self::COMMON_FOLDER . ComponentHelper::componentNameWithoutProvider($object). '/';
            if ($fileName) {
                if ($withDefaultExtension) {
                    $fileName = self::addDefaultExtensionIfNeeded($fileName);
                }
                $path .= $fileName;
            }
            $paths[$key] = $path;
        }

        return $paths[$key];
    }

    public static function addDefaultExtensionIfNeeded($fileName)
    {
        static $fileNames = [];

        $key = $fileName;

        if (!key_exists($key, $fileNames)) {
            if ($fileName &&
                !($extension = pathinfo($fileName, PATHINFO_EXTENSION))
            ) {
                $fileName .= '.' . self::DEFAULT_EXTENSION;
            }

            $fileNames[$key] = $fileName;
        }

        return $fileNames[$key];
    }

    public static function matchTemplate($object, $match, $withDefault = false)
    {
        if (is_bool($match)) {
            $withDefault = $match;
            $match = null;
        }

        $templateName = self::getTemplateName($object);

        if ($withDefault &&
            self::isDefaultTemplate($templateName)
        ) {
            return true;
        }

        return in_array($templateName, Arr::wrap($match), true);
    }

    public static function isDefaultTemplate($templateName)
    {
        $templateName = self::getTemplateName($templateName);
        return !$templateName || ($templateName === '.default');
    }

    public static function getTemplateName($mixed)
    {
        if (is_string($mixed)) {
            return $mixed;
        }

        if (is_object($mixed)) {
            return self::getComponent($mixed)->getTemplateName();
        }
    }

    public static function getComponentName($mixed)
    {
        if (is_string($mixed)) {
            return $mixed;
        }

        if (is_object($mixed)) {
            return self::getComponent($mixed)->getName();
        }
    }

    /**
     * @param \CBitrixComponent|\CBitrixComponentTemplate $object
     */
    public static function getComponent($object)
    {
        return ($object instanceof \CBitrixComponent) ? $object : $object->getComponent();
    }

    /**
     * @param \CBitrixComponent|\CBitrixComponentTemplate $object
     */
    public static function getTemplate($object)
    {
        return ($object instanceof \CBitrixComponent) ? $object->getTemplate() : $object;
    }

    public static function componentNameWithoutProvider($componentName)
    {
        static $componentNamesWithoutProvider = [];

        $key = self::getObjectKey($componentName);

        if (!key_exists($key, $componentNamesWithoutProvider)) {
            $componentName = self::getComponentName($componentName);
            $colonPos = strpos($componentName, ':');

            if ($colonPos !== false) {
                $componentNameWithoutProvider = substr($componentName, $colonPos + 1);
            } else {
                $componentNameWithoutProvider = $componentName;
            }

            $componentNamesWithoutProvider[$key] = $componentNameWithoutProvider;
        }

        return $componentNamesWithoutProvider[$key];
    }

    protected static function getRelativeCommonPathKey($object, $fileName, $withDefaultExtension)
    {
        return ($withDefaultExtension ? '1' : '0') . '_' . self::getObjectKey($object) . '---' . $fileName;
    }

    protected static function getObjectKey($object)
    {
        if (is_object($object)) {
            return 'object-' . spl_object_id($object);
        }

        return 'not-object-' . $object;
    }

    public static function baseComponentDescriptionFromFolder($dir, $extra = [])
    {
        return array_merge([
            'NAME' => self::componentNameFromFolder($dir),
            'PATH' => [
                'ID' => self::pathIdFromFolder($dir),
            ],
        ], $extra);
    }

    public static function componentNameFromFolder($dir)
    {
        return basename(dirname($dir)) . ':' . basename($dir);
    }

    public static function pathIdFromFolder($dir)
    {
        return basename(dirname($dir));
    }

    /**
     * @see \CBitrixComponent::initComponentTemplate
     */
    public static function templatePageWithoutExtension($object)
    {
        if (is_object($object)) {
            /** @var \CBitrixComponentTemplate $object */
            $object = self::getTemplate($object);
            $templatePage = $object->GetPageName();
            return pathinfo($templatePage, PATHINFO_FILENAME);
        }

        return $object;
    }
}