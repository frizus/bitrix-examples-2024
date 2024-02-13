<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 * @var string $templateFolder
 */
if (!isset($arResult['ITEM'])) {
    return;
}

include commonTemplatePath($this, 'before_template');
?>
<div>
    <?
    include commonTemplatePath($this, 'photos');
    ?>
    <div>
        <a href="<?= attr($item['DETAIL_PAGE_URL']) ?>" data-entity="select-item">
            <?= e($item['NAME']) ?>
        </a>
    </div>
    <?
    include commonTemplatePath($this, 'after_template');
    ?>
</div>
