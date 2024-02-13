<? use Bitrix\Main\Localization\Loc;
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (matchTemplate($this, true)) {
    $this->addExternalJs(commonTemplateRelativePath($this, 'script.js'));
}
Loc::loadMessages(commonTemplatePath($this, 'template'));
$this->setFrameMode(true);

$item = $arResult['ITEM'];
$areaId = $arResult['AREA_ID'];
$itemIds = array(
    'ID' => $areaId,
    'PICT' => $areaId.'_pict',
    // ...