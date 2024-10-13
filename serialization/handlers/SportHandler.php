<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsHelpers\SportVariants\Persist\SportPersistVariant;
use SportsPlanning\Field;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

/**
 * @psalm-type _Field = array{number: int}
 * @psalm-type _FieldValue = array{input: Input, fields: list<_Field>}
 */
class SportHandler extends Handler implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Sport::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return Sport
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Sport {
        $fieldValue['persistVariant'] = $fieldValue;
        /** @var SportPersistVariant $sportVariant */
        $sportVariant = $this->getProperty(
            $visitor,
            $fieldValue,
            'persistVariant',
            SportPersistVariant::class
        );

        $sport = new Sport($fieldValue['input'], $sportVariant);
        foreach ($fieldValue['fields'] as $arrField) {
            new Field($sport, $arrField['number']);
        }
        return $sport;
    }
}
