<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$areaIds = [];
echo '<!-- items-container -->';
foreach ($arResult['ITEMS'] as $key => $item) {
    include commonTemplatePath($this, 'item');
}
echo '<!-- items-container -->';