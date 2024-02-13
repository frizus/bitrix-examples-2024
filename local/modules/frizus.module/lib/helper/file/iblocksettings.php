<?
namespace Frizus\Module\Helper\File;

class IBlockSettings
{
    public static function getSize($iblockId, $defaultWidth = 2000, $defaultHeight = 2000)
    {
        static $defs = [], $set = [];
        if (!isset($set[$iblockId])) {
            $arIBlock = \CIBlock::GetArrayByID($iblockId);

            if ($arIBlock === false) {
                $def = null;
            } else {
                $def = $arIBlock["FIELDS"]["DETAIL_PICTURE"]["DEFAULT_VALUE"];
                if (!isset($def['WIDTH']) || !strlen($def['WIDTH']) || !isset($def['HEIGHT']) || !strlen($def['HEIGHT'])) {
                    $def = null;
                } else {
                    $def += [
                        'width' => (int)$def['WIDTH'],
                        'height' => (int)$def['HEIGHT'],
                    ];
                }
            }

            $defs[$iblockId] = $def;
            $set[$iblockId] = true;
        }

        return $defs[$iblockId] ?? [
            'WIDTH' => $defaultWidth,
            'HEIGHT' => $defaultHeight,
            'METHOD' => 'resample',
            'COMPRESSION' => 100,

            'width' => $defaultWidth,
            'height' => $defaultHeight
        ];
    }
}