<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var CBitrixComponentTemplate $this
 */
include commonTemplatePath($this, 'before_template');
include commonTemplatePath($this, 'top_pager');
if (!empty($arResult['ITEMS'])) {
    ?>
    <div>
        <?include commonTemplatePath($this, 'items');?>
    </div>
<?
}
include commonTemplatePath($this, 'lazyload');
include commonTemplatePath($this, 'bottom_pager');
include commonTemplatePath($this, 'after_template');