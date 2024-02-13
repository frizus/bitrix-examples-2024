<?php
namespace Frizus\IBlock;

use Bitrix\Iblock\Component\Tools;
use Frizus\PointerArrayAccess;
use Traversable;

class ElementData implements \ArrayAccess, \Countable, \IteratorAggregate, \Serializable
{
    protected $data;

    /**
     * @var \_CIBElement
     */
    protected $CIBElement;

    protected $propertiesLoaded = false;

    protected $gotDisplayProperties = false;

    protected $picturesLoaded = [];

    protected $sectionLoaded = false;

    public const LOADABLE_PROPERTIES = [
        'PROPERTIES',
        'DISPLAY_PROPERTIES',
        'DETAIL_PICTURE_SRC',
        'DETAIL_PICTURE_FILE',
        'PREVIEW_PICTURE_SRC',
        'PREVIEW_PICTURE_FILE',
        'IBLOCK_SECTION',
    ];

    public function __construct($CIBElement)
    {
        $this->CIBElement = $CIBElement;
        $this->data = $this->CIBElement->GetFields();
        $this->propertiesLoaded = false;
        $this->gotDisplayProperties = false;
    }

    public function getIterator(): Traversable
    {
        return (function () {
            foreach ($this->data as $key => $val) {
                yield $key => $val;
            }
        })();
    }

    public function count(): int
    {
        return $this->count($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!key_exists($offset, $this->data)) {
            if (in_array($offset, $this::LOADABLE_PROPERTIES, true)) {
                $this->load($offset);
            }
        }
        return isset($this->data[$offset]);
    }

    public function &offsetGet(mixed $offset): mixed
    {
        $this->load($offset);
        return $this->data[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    protected function load($offset)
    {
        if (key_exists($offset, $this->data)) {
            return;
        }

        if ($offset === 'PROPERTIES') {
            $this->loadProperties();
        } elseif ($offset === 'DISPLAY_PROPERTIES') {
            $this->getDisplayProperties();
        } elseif (($offset === 'DETAIL_PICTURE_SRC') || ($offset === 'DETAIL_PICTURE_FILE')) {
            $this->loadPicture('DETAIL_PICTURE');
        } elseif (($offset === 'PREVIEW_PICTURE') || ($offset === 'PREVIEW_PICTURE_FILE')) {
            $this->loadPicture('PREVIEW_PICTURE');
        } elseif ($offset === 'IBLOCK_SECTION') {
            $this->loadSection();
        }
    }

    protected function loadSection()
    {
        if ($this->sectionLoaded) {
            return;
        }

        if ($this->data['IBLOCK_SECTION_ID']) {
            $this->data['IBLOCK_SECTION'] = \CIBlockSection::GetNavChain($this->data['IBLOCK_ID'], $this->data['IBLOCK_SECTION_ID'], [], true);
        } else {
            $this->data['IBLOCK_SECTION'] = false;
        }
        $this->sectionLoaded = true;
    }

    protected function loadPicture($field)
    {
        if (key_exists($field, $this->picturesLoaded)) {
            return;
        }

        $this->picturesLoaded[$field] = true;
        $this->data[$field . '_FILE'] = $this->data[$field] ? \CFile::GetFileArray($this->data[$field]) : null;
        $this->data[$field . '_SRC'] = $this->data[$field . '_FILE'] ? $this->data[$field . '_FILE']['SRC'] : null;
    }

    protected function loadProperties()
    {
        if ($this->propertiesLoaded) {
            return;
        }

        $this->data['PROPERTIES'] = $this->CIBElement->GetProperties();
        $this->propertiesLoaded = true;
        $this->CIBElement = null;
    }

    public function getDisplayProperties()
    {
        if ($this->gotDisplayProperties) {
            return;
        }

        $this->loadProperties();
        $this->gotDisplayProperties = true;
        $this->data['DISPLAY_PROPERTIES'] = [];

        foreach ($this->data['PROPERTIES'] as $code => $property) {
            $isArr = is_array($property);
            if (
                ($isArr && !empty($property['VALUE']))
                || (!$isArr && (string)$property['VALUE'] !== '')
                || Tools::isCheckboxProperty($property)
            ) {
                $this->data['DISPLAY_PROPERTIES'][$code] = \CIBlockFormatProperties::GetDisplayValue($this->data, $property);
            }
        }
    }

    public function serialize()
    {
        foreach ($this::LOADABLE_PROPERTIES as $prop) {
            $this->load($prop);
        }
        return $this->serialize($this->data);
    }

    public function unserialize(string $data)
    {
        $this->data = unserialize($data);
    }

    public function __serialize(): array
    {
        return $this->serialize();
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data;
    }
}