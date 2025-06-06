<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsPlanning\Poule;

/**
 * @psalm-type _Place = array{placeNr: int}
 * @psalm-type _FieldValue = array{pouleNr: int, places: list<_Place>}
 */
final class PouleHandler extends Handler implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Poule::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return Poule
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Poule {
        return new Poule($fieldValue['pouleNr'], count($fieldValue['places']));
    }
}
