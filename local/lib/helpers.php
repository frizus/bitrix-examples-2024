<?php
use Bitrix\Sale\Basket;
use Frizus\Cache\Cache;
use Frizus\Cart\CartHelper;
use Frizus\Cart\PriceHelper;
use Frizus\FieldSelectorHelper;
use Frizus\GetAndCache;
use Frizus\Module\Helper\ComponentHelper;
use Frizus\Module\Helper\CurrencyHelper;
use Frizus\Module\Helper\Str;

function classMatch($needle, $haystack)
{
    return Str::classMatch($needle, $haystack);
}

function toCur($price, $from, $to, $emptyOnError = false)
{
    return PriceHelper::convertCurrency($price, $from, $to, $emptyOnError);
}

function toRub($price, $from)
{
    return toCur($price, $from, CurrencyHelper::getRoubles(), true);
}

function getField($object, $key)
{
    return FieldSelectorHelper::getField($object, $key);
}

function getIntField($object, $key)
{
    return FieldSelectorHelper::getIntField($object, $key);
}

function getFloatField($object, $key)
{
    return FieldSelectorHelper::getFloatField($object, $key);
}

function cache()
{
    return new Cache();
}

function attr($string) {
    return htmlspecialcharsbx($string, ENT_COMPAT, false);
}

function e($string) {
    return htmlspecialcharsbx($string, ENT_COMPAT, false);
}

function text($string) {
    return html_entity_decode(strip_tags($string));
}

function is_true($value) {
    return in_array($value, ['Y', true, '1', 1], true);
}

function not_true($value) {
    return !is_true($value);
}

function is_false($value) {
    return in_array($value, ['N', false, '0', 0], true);
}

function not_false($value) {
    return !is_false($value);
}

function to_YN($value) {
    return _to_bool($value, 'is_true', 'Y', 'N');
}

function to_bool($value) {
    return _to_bool($value, 'is_true', true, false);
}

function to_int_bool($value) {
    return _to_bool($value, 'is_true', 1, 0);
}

function to_string_int_bool($value) {
    return _to_bool($value, 'is_true', '1', '0');
}

function _to_bool($value, $closure, $true, $false) {
    return call_user_func($closure, $value) ? $true : $false;
}

function validate_int($value) {
    return (filter_var($value, FILTER_VALIDATE_INT) !== false);
}

function validate_float($value) {
    return (filter_var($value, FILTER_VALIDATE_FLOAT) !== false);
}

function is_id($id) {
    return validate_int($id) &&
        ((int)$id > 0);
}

function is_empty($value) {
    return is_null($value) ||
        ($value === '');
}

function is_not_empty($value) {
    return !is_empty($value);
}

function is_success($result) {
    return is_filled_success($result) ||
        ($result === true);
}

function is_filled_success($result) {
    return (
        is_array($result) &&
        ($result['status'] === 'success')
    );
}

function is_error($result) {
    return is_empty_error($result) ||
        is_filled_error($result);
}

function is_empty_error($result) {
    return $result === false;
}

function is_filled_error($result) {
    return is_array($result) &&
        ($result['status'] !== 'success');
}

function is_message($result) {
    return is_array($result) &&
        key_exists('message', $result);
}

function getIblockId($code)
{
    static $iblocks = [];

    if (!$code) {
        return false;
    }

    if (!isset($iblocks[$code])) {
        $result = \Bitrix\Iblock\IblockTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=CODE' => $code,
            ]
        ]);

        if ($row = $result->fetch()) {
            $iblocks[$code] = (int)$row['ID'];
        } else {
            $iblocks[$code] = false;
        }
    }

    return $iblocks[$code];
}

function clearGetAndCache($key, $extra = [], $alsoCacheAs = [], $ids = null)
{
    return GetAndCache::getInstance()->clearGetAndCache($key, $extra, $alsoCacheAs, $ids);
}

function getAlsoCacheAs(...$args)
{
    $debug = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];
    $key = GetAndCache::getInstance()->getKeyFromBackTrace($debug);

    return GetAndCache::getInstance()->getAlsoCacheAs($args, $key);
}

function getCacheMethodKey($class, $method = null, $extra = null)
{
    return GetAndCache::getInstance()->getMethodKey($class, $method, $extra);
}

function getAndCachePerKey($closure, $keys, $key = null, $extraParams = [], $alsoCacheAs = [], $alsoCacheAsCacheNull = false)
{
    if (!$key) {
        $debug = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];
        $key = GetAndCache::getInstance()->getKeyFromBackTrace($debug, $extraParams);
    }
    return GetAndCache::getInstance()->getAndCachePerKey($closure, $keys, $key, $extraParams, $alsoCacheAs, $alsoCacheAsCacheNull);
}

function getAndCache($closure, $keys, $key = null, $extraParams = [], $alsoCacheAs = [], $alsoCacheAsCacheNull = false)
{
    if (!$key) {
        $debug = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2)[1];
        $key = GetAndCache::getInstance()->getKeyFromBackTrace($debug, $extraParams);
    }
    return GetAndCache::getInstance()->getAndCache($closure, $keys, $key, $extraParams, $alsoCacheAs, $alsoCacheAsCacheNull);
}

/**
 * @param Basket|array $basket
 */
function getBasketItemByCode($basket, $basketCode)
{
    return CartHelper::getBasketItemByCode($basket, $basketCode);
}

function getBasketItemCode($basketItem, $index, &$basket = null)
{
    return CartHelper::getBasketItemCode($basketItem, $index, $basket);
}

/**
 * @param CBitrixComponent|CBitrixComponentTemplate|string $object
 * @param string|null $fileName
 * @param bool $withDefaultExtension
 * @return string
 */
function commonTemplatePath($object, $fileName = null, $withDefaultExtension = true)
{
    return ComponentHelper::getCommonPath($object, $fileName, $withDefaultExtension);
}

/**
 * @param CBitrixComponent|CBitrixComponentTemplate|string $object
 * @param string|null $fileName
 * @param bool $withDefaultExtension
 * @return string
 */
function commonTemplateRelativePath($object, $fileName = null, $withDefaultExtension = true)
{
    return ComponentHelper::getRelativeCommonPath($object, $fileName, $withDefaultExtension);
}

/**
 * @param CBitrixComponent|CBitrixComponentTemplate|string $templateName
 * @return bool
 */
function isDefaultTemplate($templateName)
{
    return ComponentHelper::isDefaultTemplate($templateName);
}

/**
 * @param CBitrixComponent|CBitrixComponentTemplate|string $object
 * @param null|string|string[]|true $match
 * @param bool $withDefault
 * @return bool
 */
function matchTemplate($object, $match, $withDefault = false)
{
    return ComponentHelper::matchTemplate($object, $match, $withDefault);
}