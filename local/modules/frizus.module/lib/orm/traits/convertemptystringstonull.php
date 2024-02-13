<?php
namespace Frizus\Module\ORM\Traits;

use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM\Objectify\EntityObject;

trait ConvertEmptyStringsToNull
{
    public static function convertEmptyStringsToNullBeforeSave(Event $event)
    {
        /** @var Entity $entity */
        /** @var EntityObject $object */
        $entity = $event->getEntity();
        $fields = $event->getParameter('fields');
        $object = $event->getParameter('object');

        foreach ($fields as $fieldName => $value) {
            if ($value !== '') {
                continue;
            }

            /** @var Field $field */
            $field = $entity->getField($fieldName);

            if (method_exists($field, 'isNullable')) {
                if ($field->isNullable()) {
                    $object[$fieldName] = null;
                }
            }
        }
    }
}
