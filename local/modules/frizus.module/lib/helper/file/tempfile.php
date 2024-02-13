<?
namespace Frizus\Module\Helper\File;

use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\Path;

class TempFile
{
    public static function getTempNameAndCreateDirectory($extension = false)
    {
        $tmpName = self::getTempName($extension);

        $directory = Path::getDirectory($tmpName);
        Directory::createDirectory($directory);

        return $tmpName;
    }

    public static function getTempName($extension = false)
    {
        $tmpName = 'tmp.' . md5(mt_rand());

        if ($extension) {
            $tmpName .= '.' . $extension;
        }

        return \CFile::GetTempName('', $tmpName);
    }

    public static function saveBinaryToTemp($binary, $extension = false)
    {
        $tmpName = self::getTempName($extension);
        $directory = Path::getDirectory($tmpName);
        Directory::createDirectory($directory);

        if (file_put_contents($tmpName, $binary) === false) {
            self::deleteDirectory($directory);
            return false;
        }

        return $tmpName;
    }

    public static function deleteTempFileAndFolder($tmpName)
    {
        if (file_exists($tmpName) &&
            is_file($tmpName) &&
            self::isInTempDir($tmpName)
        ) {
            if (@unlink($tmpName)) {
                $directory = Path::getDirectory($tmpName);

                if (self::isInTempDir($directory) &&
                    self::directoryIsEmpty($directory)
                ) {
                    if (!@rmdir($directory)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public static function deleteDirectory($path, $isHavingFiles = true)
    {
        $fileExists = file_exists($path);

        if ($fileExists && is_file($path)) {
            return false;
        }

        if ($fileExists &&
            is_dir($path) &&
            self::isInTempDir($path)
        ) {

            if (!$isHavingFiles) {
                if (!@rmdir($path)) {
                    return false;
                }
            } else {
                return self::deleteDirectoryRecursive($path);
            }
        }

        return true;
    }

    /**
     * https://stackoverflow.com/questions/1653771/how-do-i-remove-a-directory-that-is-not-empty
     */
    protected static function deleteDirectoryRecursive($path)
    {
        $entries = scandir($path);

        foreach ($entries as $entry) {
            if (($entry !== '.') && ($entry !== '..')) {
                $entryAbsPath = $path . '/' . $entry;

                if (!self::isInTempDir($entryAbsPath)) {
                    continue;
                }

                if (filetype($entryAbsPath) === 'dir') {
                    if (!self::deleteDirectoryRecursive($entryAbsPath)) {
                        return false;
                    }
                } else {
                    if (!@unlink($entryAbsPath)) {
                        return false;
                    }
                }
            }
        }

        return @rmdir($path);
    }

    public static function directoryIsEmpty($path)
    {
        $handle = opendir($path);

        if ($handle === false) {
            return false;
        }

        while (false !== ($entry = readdir($handle))) {
            if (($entry !== '.') && ($entry !== '..')) {
                closedir($handle);
                return false;
            }
        }

        closedir($handle);
        return true;
    }

    public static function isInTempDir($tempName)
    {
        static $tempDir;

        if (!isset($tempDir)) {
            $tempDir = rtrim(preg_replace('#/{2,}#', '/', \CTempFile::GetAbsoluteRoot()), '/') . '/';
        }

        $tempName = preg_replace('#/{2,}#', '/', $tempName);

        return (mb_strpos($tempName, $tempDir) === 0) &&
            (mb_strlen($tempName) > mb_strlen($tempDir));
    }
}