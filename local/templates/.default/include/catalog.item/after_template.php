<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if (!$haveOffers)
{
    if ($showProductProps)
    {
        ?>
        <div id="<?=$itemIds['BASKET_PROP_DIV']?>" style="display: none;">
            <?
            if (!empty($item['PRODUCT_PROPERTIES_FILL']))
            {
                // ...