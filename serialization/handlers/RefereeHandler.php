<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsPlanning\Input;

use SportsPlanning\Referee;

/**
 * @psalm-type _FieldValue = array{input: Input, number: int, priority: int}
 */
final class RefereeHandler extends Handler implements SubscribingHandlerInterface
{
    #[\Override]
    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(Referee::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return Referee
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): Referee {
        $referee = new Referee($fieldValue['input'], $fieldValue['number']);
        $referee->setPriority($fieldValue['priority']);
        return $referee;
    }
}
