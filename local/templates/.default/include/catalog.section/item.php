<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$uniqueId = $item['ID'] . '_' . md5($this->randString() . $component->getAction());
$areaIds[$item['ID']] = $this->GetEditAreaId($uniqueId);
$this->AddEditAction($uniqueId, $item['EDIT_LINK'], $elementEdit);
$this->AddDeleteAction($uniqueId, $item['DELETE_LINK'], $elementDelete, $elementDeleteParams);

$APPLICATION->IncludeComponent(
    'bitrix:catalog.item',
    $itemTemplateName ?? '',
    array(
        'RESULT' => array(
            'ITEM' => $item,
            'AREA_ID' => $areaIds[$item['ID']],
            'TYPE' => 'CARD',
            'BIG_LABEL' => 'N',
            'BIG_DISCOUNT_PERCENT' => 'N',
            'BIG_BUTTONS' => 'N',
            'SCALABLE' => 'N'
        ),
        'PARAMS' => $generalParams
            + array(
                'SKU_PROPS' => $arResult['SKU_PROPS'][$item['IBLOCK_ID']],
            )
    ),
    $component,
    array('HIDE_ICONS' => 'Y')
);