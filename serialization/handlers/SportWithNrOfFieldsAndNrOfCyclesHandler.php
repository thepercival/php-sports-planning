<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

/**
 * @psalm-type _Place = array{placeNr: int}
 * @psalm-type _Sport = array{nrOfGamePlaces: int}|array{nrOfHomePlaces: int, nrOfAwayPlaces: int}
 * @psalm-type _FieldValue = array{sport: _Sport, nrOfFields: int, nrOfCycles: int}
 */
final class SportWithNrOfFieldsAndNrOfCyclesHandler extends Handler implements SubscribingHandlerInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
    #[\Override]
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(SportWithNrOfFieldsAndNrOfCycles::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param _FieldValue $fieldValue
     * @param array<string, array> $type
     * @param Context $context
     * @return SportWithNrOfFieldsAndNrOfCycles
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): SportWithNrOfFieldsAndNrOfCycles {
        return new SportWithNrOfFieldsAndNrOfCycles(
            $this->determineSport($fieldValue['sport']),
            $fieldValue['nrOfFields'],
            $fieldValue['nrOfCycles']
        );
    }

    /**
     * @param _Sport $fieldValueSport
     * @return TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo
     * @throws \Exception
     */
    public function determineSport(array $fieldValueSport): TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo {
        ;
        if( isset($fieldValueSport['nrOfGamePlaces'])) {
            return new TogetherSport($fieldValueSport['nrOfGamePlaces']);
        }
        if( isset($fieldValueSport['nrOfHomePlaces']) && isset($fieldValueSport['nrOfAwayPlaces'])) {
            if( $fieldValueSport['nrOfHomePlaces'] === 1 && $fieldValueSport['nrOfAwayPlaces'] === 1) {
                return new AgainstOneVsOne();
            } else if( $fieldValueSport['nrOfHomePlaces'] === 1 && $fieldValueSport['nrOfAwayPlaces'] === 2) {
                return new AgainstOneVsTwo();
            } else if( $fieldValueSport['nrOfHomePlaces'] === 2 && $fieldValueSport['nrOfAwayPlaces'] === 2) {
                return new AgainstTwoVsTwo();
            }
        }
        throw new \Exception('unknown sport');
    }


}
