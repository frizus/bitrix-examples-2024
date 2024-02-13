<?
namespace Frizus\Module\Helper;

class FileHelper
{
    public static function formatSize($bytes)
    {
        static $byteUnits = ["Б", "КБ", "МБ", "ГБ"];
        static $bytePrecision = [0, 0, 1, 2, 2, 3, 3, 4, 4];
        static $byteNext = 1024;
        $bytes = (int)$bytes;
        for ($i = 0; ($bytes / $byteNext) >= 0.9 && $i < count($byteUnits); $i++) {
            $bytes /= $byteNext;
        }

        return round($bytes, $bytePrecision[$i]) . ' ' . $byteUnits[$i];
    }

    public static function normalizeFolderName($name, $allowFolders = false, $encode = false)
    {
        $search = [
            '<',
            '>',
            ':',
            '"',
            '|',
            '?',
            '*',
        ];

        if ($allowFolders) {
            $name = str_replace("\\", '/', $name);
        } else {
            $search = array_merge($search, [
                '/',
                "\\",
            ]);
        }

        $normalized = str_replace(
            $search,
            '_',
            $name
        );

        if ($encode) {
            if ($allowFolders) {
                $parts = explode('/', $normalized);
                foreach ($parts as &$part) {
                    $part = rawurlencode($part);
                }
                unset($part);
                $normalized = implode('/', $parts);
            } else {
                $normalized = rawurldecode($normalized);
            }
        }

        return $normalized;
    }

    public static function getFileName($path)
    {
        $path = str_replace("\\", '/', $path);

        $lastSlash = strrpos($path, '/');
        if ($lastSlash !== false) {
            return substr($path, $lastSlash + 1);
        }

        return $path;
    }
}