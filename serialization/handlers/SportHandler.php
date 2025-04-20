<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use SportsPlanning\Field;
use SportsPlanning\Input;
// use SportsPlanning\Sports\Plannable\PlannableSport;

/**
 * @psalm-type _Field = array{number: int}
 * @psalm-type _FieldValue = array{input: Input, fields: list<_Field>}
 */
class SportHandler extends Handler // implements SubscribingHandlerInterface
{
//    /**
//     * @psalm-return list<array<string, int|string>>
//     */
//    public static function getSubscribingMethods(): array
//    {
//        return static::getDeserializationMethods(PlannableSport::class);
//    }

    // @TODO CDK
//    /**
//     * @param JsonDeserializationVisitor $visitor
//     * @param _FieldValue $fieldValue
//     * @param array<string, array> $type
//     * @param Context $context
//     * @return PlannableSport
//     */
//    public function deserializeFromJson(
//        JsonDeserializationVisitor $visitor,
//        array $fieldValue,
//        array $type,
//        Context $context
//    ): PlannableSport {
//        $fieldValue['persistVariant'] = $fieldValue;
//        /** @var SportPersistVariant $sportVariant */
//        $sportVariant = $this->getProperty(
//            $visitor,
//            $fieldValue,
//            'persistVariant',
//            SportPersistVariant::class
//        );
//
//        $sport = new PlannableSport($fieldValue['input'], $sportVariant);
//        foreach ($fieldValue['fields'] as $arrField) {
//            new Field($sport, $arrField['number']);
//        }
//        return $sport;
//    }
}
