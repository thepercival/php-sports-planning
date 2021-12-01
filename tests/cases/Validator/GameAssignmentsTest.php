<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Validator;

use Exception;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Planning\Validator\GameAssignments as GameAssignmentValidator;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class GameAssignmentsTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

    public function testGetCountersFields(): void
    {
        $planning = $this->createPlanning($this->createInput([5]));

        $validator = new GameAssignmentValidator($planning);
        $gameCounters = $validator->getCounters(GameAssignmentValidator::FIELDS);

        $fieldGameCounters = $gameCounters[GameAssignmentValidator::FIELDS];
        $field = $planning->getInput()->getSport(1)->getField(1);
        $gameFieldCounter = $fieldGameCounters[$field->getUniqueIndex()];
        self::assertSame($field, $gameFieldCounter->getResource());
        self::assertSame(5, $gameFieldCounter->getNrOfGames());
    }

    public function testGetCountersReferees(): void
    {
        $planning = $this->createPlanning($this->createInput([5]));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $gameCounters = $validator->getCounters(GameAssignmentValidator::REFEREES);

        /** @var GameCounter[] $gameRefereeCounters */
        $gameRefereeCounters = $gameCounters[GameAssignmentValidator::REFEREES];
        $referee = $planning->getInput()->getReferee(1);
        $gameRefereeCounter = $gameRefereeCounters[(string)$referee->getNumber()];
        self::assertSame($referee, $gameRefereeCounter->getResource());
        self::assertSame(5, $gameRefereeCounter->getNrOfGames());
    }

    public function testGetCountersRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SamePoule)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $gameCounters = $validator->getCounters(GameAssignmentValidator::REFEREEPLACES);

        /** @var GameCounter[] $gameRefereePlaceCounters */
        $gameRefereePlaceCounters = $gameCounters[GameAssignmentValidator::REFEREEPLACES];
        $place = $planning->getInput()->getPoule(1)->getPlace(1);
        $gameRefereePlaceCounter = $gameRefereePlaceCounters[$place->getLocation()];
        self::assertSame($place, $gameRefereePlaceCounter->getResource());
        self::assertSame(2, $gameRefereePlaceCounter->getNrOfGames());
    }

    public function testGetUnequalRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SamePoule)
        );

        $firstPoule = $planning->getInput()->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoule
            || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $unequals = $validator->getRefereePlaceUnequals();


        self::assertCount(1, $unequals);
        $firstUnequal = reset($unequals);
        self::assertNotFalse($firstUnequal);
        $minGameCounters = $firstUnequal->getMinGameCounters();
        $maxGameCounters = $firstUnequal->getMaxGameCounters();

        self::assertSame(2, $firstUnequal->getDifference());
        self::assertCount(1, $minGameCounters);
        self::assertCount(1, $maxGameCounters);

        /** @var GameCounter $minGameCounter */
        $minGameCounter = reset($minGameCounters);
        /** @var GameCounter $maxGameCounter */
        $maxGameCounter = reset($maxGameCounters);
        self::assertSame($replacedPlace, $minGameCounter->getResource());
        self::assertSame($replacedByPlace, $maxGameCounter->getResource());
        self::assertSame(1, $minGameCounter->getNrOfGames());
        self::assertSame(3, $maxGameCounter->getNrOfGames());
    }

    public function testValidateRefereePlacesTwoPoulesNotEqualySized(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5, 4], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::OtherPoules)
        );

        $secondPoule = $planning->getInput()->getPoule(2);
        $replacedPlace = $secondPoule->getPlace(4);
        $replacedByPlace = $secondPoule->getPlace(3);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoule
                         || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $unequals = $validator->getRefereePlaceUnequals();

        self::assertCount(1, $unequals);
    }

    public function testValidateUnequalFields(): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(2);
        $planning = $this->createPlanning(
            $this->createInput([5], [$sportVariant])
        );

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedField = $planning->getInput()->getSport(1)->getField(2);
        $replacedByField = $planning->getInput()->getSport(1)->getField(1);
        $this->replaceField($planning->createFirstBatch(), $replacedField, $replacedByField);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalReferees(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, 3)
        );

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedReferee = $planning->getInput()->getReferee(2);
        $replacedByReferee = $planning->getInput()->getReferee(1);
        $firstBatch = $planning->createFirstBatch();
        self::assertInstanceOf(Batch::class, $firstBatch);
        $this->replaceReferee($firstBatch, $replacedReferee, $replacedByReferee);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SamePoule)
        );

        $firstPoule = $planning->getInput()->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchOtherPoule
                         || $firstBatch instanceof SelfRefereeBatchSamePoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === SelfReferee::SamePoule,
            $firstBatch,
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testEquallyAssignedFieldsMultipleSport(): void
    {
        $sportVariant1 = $this->getAgainstSportVariantWithFields(4);
        $sportVariant2 = $this->getAgainstSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput([5], [$sportVariant1, $sportVariant2])
        );

        $validator = new GameAssignmentValidator($planning);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }

    public function testValidate(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SamePoule)
        );
//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);
        $validator = new GameAssignmentValidator($planning);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }
}
