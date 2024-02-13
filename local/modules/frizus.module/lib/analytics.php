<?
namespace Frizus\Module;

use Bitrix\Main\Loader;
use Frizus\IBlock\Product;

class Analytics
{
    public const BRAND_PROPERTY_CODE = '';

    public static function mergeEcommerce($ecommerce, $ecommerce2)
    {
        if (array_key_exists('items', $ecommerce)) {
            $maxIndex = end($ecommerce['items'])['index'];
        }

        $index = ($maxIndex ?? 0) + 1;
        foreach ($ecommerce2['items'] as &$item) {
            $item['index'] = $index;
            $index++;
        }
        unset($item);

        $ecommerce['items'] = array_merge($ecommerce['items'] ?? [], $ecommerce2['items'] ?? []);
        return $ecommerce;
    }

    public static function getEcommerce($ids, $itemListName = null, $currency = 'RUB', $prices = [], $quantities = null)
    {
        if (!$ids) {
            return false;
        }

        if (!is_array($ids)) {
            $prices = [$ids => $prices];
            $quantities = [$ids => $quantities];
            $ids = [$ids];
        }

        $rows = Product::getBothProductAndOffer($ids);
        Product::getSkuProps($ids);
        $items = [];
        $index = 1;
        foreach ($ids as $id) {
            $parentRow = $rows[$id]['product'];
            if (!($row = $rows[$id]['offer']) &&
                !($row = $parentRow)
            ) {
                continue;
            }

            $isOffer = (bool)$rows[$id]['offer'];
            $itemId = $parentRow['ID'];

            if ($isOffer) {
                $skuProps = Product::getSkuProps($id);
                $variant = '';
                if (is_array($skuProps)) {
                    if (count($skuProps) > 1) {
                        $first = true;
                        foreach ($skuProps as $name => $value) {
                            if (!$first) {
                                $variant .= ', ';
                            } else {
                                $first = false;
                            }
                            $variant .= $name . ': ' . $value;
                        }
                    } else {
                        $variant .= reset($skuProps);
                    }
                }
                if ($variant) {
                    $itemId .= ' ' . $variant;
                }
            }

            $item = [
                'item_name' => $parentRow['NAME'],
                //'item_id' => $row['ID'],
                'item_id' => $itemId,
            ];

            if (isset($prices[$row['ID']])) {
                $price = $prices[$row['ID']];
                if ($price['old_price']) {
                    if ($price['new_price'] && $price['new_price'] != $price['old_price']) {
                        $item['price'] = $price['new_price'];
                        $item['discount'] = abs($price['old_price'] - $price['new_price']);
                    } elseif ($price['old_price']) {
                        $item['price'] = $price['old_price'];
                    }
                }
            }

            $item['item_brand'] = $parentRow['PROPERTIES'][static::BRAND_PROPERTY_CODE]['VALUE'];

            if ($parentRow['IBLOCK_SECTION']) {
                $length = count($parentRow['IBLOCK_SECTION']);
                $l = $length > 4 ? 4 : $length;
                for ($i = 0; $i < $l; $i++) {
                    $key = 'item_category';
                    if ($i > 0) {
                        $key .= ($i + 1);
                    }
                    $item[$key] = $parentRow['IBLOCK_SECTION'][$i]['NAME'];
                }
                if ($l > 4) {
                    $item['item_category5'] = $parentRow['IBLOCK_SECTION'][$l - 1]['NAME'];
                }
            }

            if ($isOffer && $variant) {
                $item['item_variant'] = $variant;
            }

            if ($itemListName !== false) {
                $item['item_list_name'] = $itemListName ?? 'Catalog';
            }

            $item += [
                'index' => $index,
                'quantity' => $quantities[$row['ID']] ?? 1,
            ];
            $index++;

            $items[] = $item;
        }

        return [
            'currencyCode' => $currency,
            'items' => $items,
        ];
    }
}
