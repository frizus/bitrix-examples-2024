<?php
namespace Frizus\IBlock;

use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Type\Collection;
use Frizus\IBlock\Traits\AvailableFieldAlgorithm;
use Frizus\Traits\FilterTrait;
use Frizus\Helpers\Arr;

class Product
{
    use FilterTrait;
    use AvailableFieldAlgorithm;

    public static function filterByActiveAvailable($ids)
    {
        return static::_filter($ids, true, true);
    }

    public static function filterByActive($ids)
    {
        return static::_filter($ids, true);
    }

    protected static function _filter($ids, $active = null, $catalogAvailable = null)
    {
        $isNotArray = !is_array($ids);
        $emptyResult = $isNotArray ? null : [];

        if (!($rows = ElementQuery::getProducts($ids, $active, $catalogAvailable))) {
            return $emptyResult;
        }

        return $isNotArray ? ($rows ? $ids : null) : array_keys($rows);
    }

    public static function getAllOfferIdsFromOfferIdOrProductIdFromProduct($ids, $iblockId = false, $active = null, $catalogAvailable = null)
    {
        return getAndCache(function($ids, $active, $catalogAvailable) use ($iblockId) {
            if (!($rows = ElementQuery::getProducts($ids, $active, $catalogAvailable))) {
                return [];
            }

            $result = [];
            if (($offerRows = array_filter($rows, fn($row) => $row['TYPE'] == ProductTable::TYPE_OFFER)) &&
                ($parentRows = static::getParentProducts(Arr::pluck($offerRows, 'ID'))) &&
                ($offerIds = static::getProductOfferIds(Arr::pluck($parentRows, 'ID'), $iblockId, $active, $catalogAvailable))
            ) {
                $mapper = Arr::pluck($parentRows, 'ID', true);
                $mapper = array_flip($mapper);

                foreach ($offerIds as $parentId => $singleOfferIds) {
                    $offerId = $mapper[$parentId];
                    $result[$offerId] = $singleOfferIds;
                }
            }
            if ($productRows = array_filter($rows, fn($row) => $row['TYPE'] != ProductTable::TYPE_OFFER)) {
                $keys = Arr::pluck($productRows, 'ID');
                $result += array_combine($keys, array_map(fn($id) => [$id], $keys));
            }

            return $result;
        }, $ids, null, [$active, $catalogAvailable]);
    }

    public static function getFirstOfferIdFromParent($ids, $iblockId = false, $active = null, $catalogAvailable = null)
    {
        return getAndCache(function($ids, $active, $catalogAvailable) use ($iblockId) {
            if (!($rows = ElementQuery::getProducts($ids, $active, $catalogAvailable))) {
                return [];
            }

            $result = [];
            if (($productRows = array_filter($rows, fn($row) => $row['TYPE'] == ProductTable::TYPE_SKU)) &&
                ($offerIds = static::getProductOfferIds(Arr::pluck($productRows, 'ID'), $iblockId, $active, $catalogAvailable))
            ) {
                foreach ($offerIds as $parentId => $singleOfferIds) {
                    if ($id = reset($singleOfferIds)) {
                        $result[$parentId] = $id;
                    }
                }
            }

            return $result;
        }, $ids, null, [$active, $catalogAvailable]);
    }

    public static function getOfferIdsIfTypeIsSkuOrItselfIfTypeIsProduct($ids, $iblockId = false, $active = null, $catalogAvailable = null)
    {
        return getAndCache(function($ids, $active, $catalogAvailable) use ($iblockId) {
            if (!($rows = ElementQuery::getProducts($ids, $active, $catalogAvailable))) {
                return;
            }

            $result = [];

            if (($skuRows = array_filter($rows, fn($row) => $row['TYPE'] == ProductTable::TYPE_SKU))) {
                foreach ($skuRows as $id => $row) {
                    if ($offerIds = static::getProductOfferIds($row['ID'], $iblockId, $active, $catalogAvailable)) {
                        $result[$id] = $offerIds;
                    }
                }
            }

            if (($productRows = array_filter($rows, fn($row) => $row['TYPE'] == ProductTable::TYPE_PRODUCT))) {
                $keys = array_keys($productRows);
                $result += array_combine($keys, array_map(fn($id) => [$id], $keys));
            }

            return $result;
        }, $ids, null, [$active, $catalogAvailable], getAlsoCacheAs($active, $catalogAvailable));
    }

    public static function getActiveAvailableProductOfferIds($ids, $iblockId = false)
    {
        return static::getProductOfferIds($ids, $iblockId, true, true);
    }

    public static function getActiveProductOfferIds($ids, $iblockId = false)
    {
        return static::getProductOfferIds($ids, $iblockId, true);
    }

    public static function getProductOfferIds($ids, $iblockId = false, $active = null, $catalogAvailable = null)
    {
        return getAndCachePerKey(function($id, $active, $catalogAvailable) use ($iblockId) {
            $filter = static::activeAvailableFilter($active, $catalogAvailable);

            if (!($result = \CCatalogSku::getOffersList(
                    $id,
                    $iblockId,
                    $filter,
                    [
                        'ID',
                    ]
                )) ||
                !($offers = $result[$id])
            ) {
                return;
            }

            $result = Arr::pluck($offers, 'ID');

            return $result;
        }, $ids, null, [$active, $catalogAvailable], getAlsoCacheAs($active, $catalogAvailable));
    }

    /**
     * @see \Bitrix\Iblock\Component\Element::editTemplateOfferProps
     * @TODO @see \Bitrix\Iblock\Component\Element::editTemplateItems -> \CIBlockPriceTools::getTreePropertyValues
     */
    public static function modifyAddSkuRelated(&$rows, $skuProps)
    {
        $matrix = [];
        $skuProps ??= [];
        $skuPropIds = array_keys($skuProps);
        $matrixFields = array_fill_keys($skuPropIds, false);

        foreach ($rows as $id => &$offer) {
            $offer['TREE'] = [];
            $offer['TREE_VALUES'] = [];

            $row = [];
            foreach ($skuPropIds as $code) {
                $row[$code] = static::getTemplatePropCell($code, $offer, $matrixFields, $skuProps);
                if (!is_null($nodeValue = static::getTreeNodeValue($code, $offer, $matrixFields, $skuProps))) {
                    $offer['TREE_VALUES'][$code] = $nodeValue;
                }
            }

            $matrix[$id] = $row;
        }
        unset($offer);

        $usedFields = [];
        $sortFields = [];

        foreach ($skuPropIds as $propCode) {
            $boolExist = $matrixFields[$propCode];
            foreach ($matrix as $id => $row) {
                if ($boolExist) {
                    $rows[$id]['TREE']['PROP_'.$skuProps[$propCode]['ID']] = $matrix[$id][$propCode]['VALUE'];
                    $rows[$id]['SKU_SORT_'.$propCode] = $matrix[$id][$propCode]['SORT'];
                    $usedFields[$propCode] = true;
                    $sortFields['SKU_SORT_'.$propCode] = SORT_NUMERIC;
                } else {
                    unset($matrix[$id][$propCode]);
                }
            }
        }

        foreach ($rows as &$row) {
            $row['OFFERS_PROP'] = $usedFields;
            $row['OFFERS_PROP_CODES'] = $usedFields ? base64_encode(serialize(array_keys($usedFields))) : '';
        }
        unset($row);

        if ($sortFields) {
            Collection::sortByColumn($rows, $sortFields, '', null, true);
        }
    }

    protected static function getTreeNodeValue($code, $offer, $matrixFields, $skuProps)
    {
        if (isset($offer['DISPLAY_PROPERTIES'][$code])) {
            return $offer['DISPLAY_PROPERTIES'][$code]['DISPLAY_VALUE'];
        }
    }

    public static function getTemplatePropCell($code, $offer, &$matrixFields, $skuProps)
    {
        $cell = array(
            'VALUE' => 0,
            'SORT' => PHP_INT_MAX,
            'NA' => true
        );

        if (isset($offer['DISPLAY_PROPERTIES'][$code]))
        {
            $matrixFields[$code] = true;
            $cell['NA'] = false;

            if ($skuProps[$code]['USER_TYPE'] === 'directory')
            {
                $intValue = $skuProps[$code]['XML_MAP'][$offer['DISPLAY_PROPERTIES'][$code]['VALUE']];
                $cell['VALUE'] = $intValue;
            }
            elseif ($skuProps[$code]['PROPERTY_TYPE'] === 'L')
            {
                $cell['VALUE'] = (int)$offer['DISPLAY_PROPERTIES'][$code]['VALUE_ENUM_ID'];
            }
            elseif ($skuProps[$code]['PROPERTY_TYPE'] === 'E')
            {
                $cell['VALUE'] = (int)$offer['DISPLAY_PROPERTIES'][$code]['VALUE'];
            }

            $cell['SORT'] = $skuProps[$code]['VALUES'][$cell['VALUE']]['SORT'];
        }

        return $cell;
    }

    public static function getSkuProps($ids)
    {
        return getAndCache(function($ids) {
            if (!($rows = ElementQuery::getProducts($ids))) {
                return;
            }

            $rows = Product::prepareSkuTree($rows);
            $result = [];
            $oldParentIBlockId = null;

            foreach ($rows as $id => $row) {
                if (!$row['PARENT_IBLOCK_ID']) {
                    continue;
                }

                if ($oldParentIBlockId !== $row['PARENT_IBLOCK_ID']) {
                    $skuProps = IBlock::skuProps($row['PARENT_IBLOCK_ID']);
                }

                foreach ($skuProps as $code => $skuProp) {
                    if (key_exists($code, $row['TREE_VALUES'])) {
                        $result[$id][$skuProp['NAME']] = $row['TREE_VALUES'][$code];
                    }
                }
            }

            return $result;
        }, $ids);
    }

    public static function &prepareSkuTree(&$rows, $parentId = false, $iblockId = false)
    {
        $alreadyAdded = true;
        foreach ($rows as $row) {
            if (!$row['TREE']) {
                $alreadyAdded = false;
                break;
            }
        }

        if ($alreadyAdded) {
            return $rows;
        }

        static::modifyAddRelationToParentFromOffer($rows, $parentId, $iblockId);

        if (!($parentIBlockIds = Arr::filter(Arr::pluckUnique($rows, 'PARENT_IBLOCK_ID')))) {
            return $rows;
        }

        $skuProps = IBlock::skuProps($parentIBlockIds);
        $groups = &Arr::groupBy($rows, 'PARENT_IBLOCK_ID');
        foreach ($skuProps as $parentIBlockId => $singleSkuProps) {
            static::modifyAddSkuRelated($groups[$parentIBlockId], $singleSkuProps);
        }

        return $rows;
    }

    public static function &modifyAddRelationToParentFromOffer(&$rows, $parentId = false, $iblockId = false)
    {
        foreach ($rows as &$row) {
            if ($row['MAIN_IBLOCK_ID']) {
                continue;
            }

            if ($row['TYPE'] == ProductTable::TYPE_OFFER) {
                if ($parentId && $iblockId) {
                    $row['PARENT_ID'] = $parentId;
                    $row['PARENT_IBLOCK_ID'] = $iblockId;
                } else {
                    $productInfo = Product::getProductInfo($row['ID']);
                    $row['PARENT_ID'] = $productInfo['ID'] ?? null;
                    $row['PARENT_IBLOCK_ID'] = $productInfo['IBLOCK_ID'] ?? null;
                }
                $row['MAIN_IBLOCK_ID'] = $row['PARENT_IBLOCK_ID'];
            } else {
                $row['MAIN_IBLOCK_ID'] = $row['IBLOCK_ID'];
            }
        }
        unset($row);

        return $rows;
    }

    public static function getMeasure($ids)
    {
        return static::getField($ids, 'MEASURE_NAME');
    }

    public static function getMinimalQuantity($ids, $defaultValue = 1.0)
    {
        return getAndCache(function($ids, $defaultValue) {
            $isNotArray = !is_array($ids);
            $ids = Arr::wrap($ids);
            $ratios = static::getField($ids, 'MEASURE_RATIO');
            foreach (array_diff($ids, array_keys($ratios)) as $id) {
                $ratios[$id] = $defaultValue;
            }

            return $ratios;
        }, $ids, null, [$defaultValue]);
    }

    public static function getRatio($ids)
    {
        return static::getField($ids, 'MEASURE_RATIO');
    }

    public static function getParentUrl($ids)
    {
        return static::getParentField($ids, 'DETAIL_PAGE_URL');
    }

    public static function getArticle($ids)
    {
        return static::getOfferFieldIfAvailableProductOtherwise($ids, [['PROPERTIES', 'CML2_ARTICLE', 'VALUE']]);
    }

    public static function getParentName($ids)
    {
        return static::getProductFieldIfAvailableOfferOtherwise($ids, 'NAME', true);
    }

    public static function getParentPicture($ids)
    {
        return static::getParentPictureDetailFirst($ids);
    }

    public static function getParentPictureDetailFirst($ids)
    {
        return static::getFirstAvailableFieldFirstCheckProductThenOffer($ids, ['PREVIEW_PICTURE_SRC', 'DETAIL_PICTURE_SRC']);
    }

    public static function getParentPicturePreviewFirst($ids)
    {
        return static::getFirstAvailableFieldFirstCheckProductThenOffer($ids, ['DETAIL_PICTURE_SRC', 'PREVIEW_PICTURE_SRC']);
    }

    public static function getPicture($ids)
    {
        return static::getPictureDetailFirst($ids);
    }

    public static function getPicturePreviewFirst($ids)
    {
        return static::getFirstAvailableFieldFirstCheckOfferThenProduct($ids, ['PREVIEW_PICTURE_SRC', 'DETAIL_PICTURE_SRC']);
    }

    public static function getPictureDetailFirst($ids)
    {
        return static::getFirstAvailableFieldFirstCheckOfferThenProduct($ids, ['DETAIL_PICTURE_SRC', 'PREVIEW_PICTURE_SRC']);
    }

    public static function getParentField($ids, $field)
    {
        return static::getFirstAvailableField($ids, [$field], 'product', null, null);
    }

    public static function getField($ids, $field)
    {
        return static::getFirstAvailableField($ids, [$field], null, null, null);
    }

    public static function getOfferFieldIfAvailableProductOtherwise($ids, $field, $checkIfValueIsNullOrEmptyString = false)
    {
        return static::getFirstAvailableFieldFirstCheckOfferThenProduct($ids, $field, $checkIfValueIsNullOrEmptyString);
    }

    public static function getProductFieldIfAvailableOfferOtherwise($ids, $field, $checkIfValueIsNullOrEmptyString)
    {
        return static::getFirstAvailableFieldFirstCheckProductThenOffer($ids, $field, $checkIfValueIsNullOrEmptyString);
    }

    public static function getFirstAvailableFieldFirstCheckProductThenOffer($ids, $fields, $checkIfValueIsNullOrEmptyString = false)
    {
        return static::getFirstAvailableField($ids, $fields, 'product', 'offer', $checkIfValueIsNullOrEmptyString);
    }

    public static function getFirstAvailableFieldFirstCheckOfferThenProduct($ids, $fields, $checkIfValueIsNullOrEmptyString = false)
    {
        return static::getFirstAvailableField($ids, $fields, 'offer', 'product', $checkIfValueIsNullOrEmptyString);
    }

    public static function getFirstAvailableField($ids, $fields, $mainType, $alternativeType, $checkIfValueIsNullOrEmptyString = false)
    {
        return getAndCache(function($ids, $fields, $mainType, $alternativeType, $checkIfValueIsNullOrEmptyString) {
            $fields = Arr::wrap($fields);
            $rows = static::getBothProductAndOffer($ids);
            $result = [];

            if (($mainType === null) && ($alternativeType === null)) {
                foreach ($rows as $id => $row) {
                    if (isset($row['offer'])) {
                        $type = 'offer';
                    } elseif (isset($row['product'])) {
                        $type = 'product';
                    } else {
                        continue;
                    }

                    foreach ($fields as $field) {
                        [$passes, $value] = static::firstAvailableFieldAlgorithm([...[$type], ...Arr::wrap($field)], $row, $checkIfValueIsNullOrEmptyString);
                        if ($passes) {
                            $result[$id] = $value;
                        }
                    }
                }
            } else {
                $types = array_filter([$mainType, $alternativeType]);

                foreach ($rows as $id => $row) {
                    $passes = false;

                    foreach ($types as $type) {
                        foreach ($fields as $field) {
                            [$passes, $value] = static::firstAvailableFieldAlgorithm([...[$type], ...Arr::wrap($field)], $row, $checkIfValueIsNullOrEmptyString);
                            if ($passes) {
                                break 2;
                            }
                        }
                    }

                    if (!$passes) {
                        continue;
                    }

                    $result[$id] = $value;
                }
            }

            return $result;
        }, $ids, null, [$fields, $mainType, $alternativeType, $checkIfValueIsNullOrEmptyString]);
    }

    public static function getBothProductAndOffer($ids)
    {
        return getAndCache(function($ids) {
            $rows = static::getProductInfo($ids);

            if (!($rows2 = ElementQuery::getProducts([...Arr::pluck($rows, 'ID'), ...$ids]))) {
                return;
            }

            $result = [];
            foreach ($rows as $offerId => $row) {
                $result[$offerId]['offer'] = $rows2[$offerId];
                $result[$offerId]['product'] = $rows2[$row['ID']];
            }

            if ($potentialParentIds = array_diff($ids, array_keys($rows))) {
                foreach ($potentialParentIds as $id) {
                    $result[$id]['product'] = $rows2[$id];
                }
            }

            return $result;
        }, $ids);
    }

    public static function getParentProducts($ids)
    {
        return getAndCache(function($ids) {
            if (!($rows = static::getProductInfo($ids)) ||
                !($elements = ElementQuery::getProducts(Arr::pluckUnique($rows, 'ID')))
            ) {
                return;
            }

            $result = [];
            foreach ($rows as $id => $row) {
                $result[$id] = $elements[$row['ID']] ?? null;
            }

            return $result;
        }, $ids);
    }

    public static function getProductInfo($ids)
    {
        return getAndCachePerKey(function($id) {
            if (!($row = \CCatalogSku::GetProductInfo($id)) ||
                !is_array($row) ||
                empty($row)
            ) {
                return;
            }

            return $row;
        }, $ids);
    }
}