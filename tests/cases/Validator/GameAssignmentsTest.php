<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Validator;

use \Exception;
use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\Input;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Validator\GameAssignments as GameAssignmentValidator;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;
use SportsPlanning\Planning\Output as PlanningOutput;

class GameAssignmentsTest extends TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testGetCountersFields()
    {
        $planning = $this->createPlanning(
            $this->createInput([5])
        );

        $validator = new GameAssignmentValidator($planning);
        $gameCounters = $validator->getCounters(GameAssignmentValidator::FIELDS);

        /** @var GameCounter[] $gameFieldCounters */
        $gameFieldCounters = $gameCounters[GameAssignmentValidator::FIELDS];
        $field = $planning->getField(1);
        $gameFieldCounter = $gameFieldCounters[(string)$field->getNumber()];
        self::assertSame($field, $gameFieldCounter->getResource());
        self::assertSame(5, $gameFieldCounter->getNrOfGames());
    }

    public function testGetCountersReferees()
    {
        $planning = $this->createPlanning(
            $this->createInput([5])
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $gameCounters = $validator->getCounters(GameAssignmentValidator::REFEREES);

        /** @var GameCounter[] $gameRefereeCounters */
        $gameRefereeCounters = $gameCounters[GameAssignmentValidator::REFEREES];
        $referee = $planning->getReferee(1);
        $gameRefereeCounter = $gameRefereeCounters[(string)$referee->getNumber()];
        self::assertSame($referee, $gameRefereeCounter->getResource());
        self::assertSame(5, $gameRefereeCounter->getNrOfGames());
    }

    public function testGetCountersRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, null, null, Input::SELFREFEREE_SAMEPOULE)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $gameCounters = $validator->getCounters(GameAssignmentValidator::REFEREEPLACES);

        /** @var GameCounter[] $gameRefereePlaceCounters */
        $gameRefereePlaceCounters = $gameCounters[GameAssignmentValidator::REFEREEPLACES];
        $place = $planning->getPoule(1)->getPlace(1);
        $gameRefereePlaceCounter = $gameRefereePlaceCounters[$place->getLocation()];
        self::assertSame($place, $gameRefereePlaceCounter->getResource());
        self::assertSame(2, $gameRefereePlaceCounter->getNrOfGames());
    }

    public function testGetUnequalRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, null, null, Input::SELFREFEREE_SAMEPOULE)
        );

        $firstPoule = $planning->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE,
            $planning->createFirstBatch(),
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $unequals = $validator->getRefereePlaceUnequals();

        self::assertCount(1, $unequals);
        $unequal = reset($unequals);
        $minGameCounters = $unequal->getMinGameCounters();
        $maxGameCounters = $unequal->getMaxGameCounters();

        self::assertSame(2, $unequal->getDifference());
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

    public function testValidateRefereePlacesTwoPoulesNotEqualySized()
    {
        $planning = $this->createPlanning(
            $this->createInput([5,4], null, null, null, Input::SELFREFEREE_OTHERPOULES)
        );

        $secondPoule = $planning->getPoule(2);
        $replacedPlace = $secondPoule->getPlace(4);
        $replacedByPlace = $secondPoule->getPlace(3);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE,
            $planning->createFirstBatch(),
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        $unequals = $validator->getRefereePlaceUnequals();

        self::assertCount(1, $unequals);
    }

    public function testValidateUnequalFields()
    {
        $sportConfigs = [new SportConfig(new SportBase(2), 2, 1)];
        $planning = $this->createPlanning(
            $this->createInput([5], GameMode::AGAINST, $sportConfigs)
        );

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedField = $planning->getField(2);
        $replacedByField = $planning->getField(1);
        $this->replaceField($planning->createFirstBatch(), $replacedField, $replacedByField);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalReferees()
    {
        $planning = $this->createPlanning(
            $this->createInput([5], GameMode::AGAINST, null, 3)
        );

        // $planningGames = $planning->getPoule(1)->getGames();
        $replacedReferee = $planning->getReferee(2);
        $replacedByReferee = $planning->getReferee(1);
        $this->replaceReferee($planning->createFirstBatch(), $replacedReferee, $replacedByReferee);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidateUnequalRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput([5], GameMode::AGAINST, null, null, Input::SELFREFEREE_SAMEPOULE)
        );

        $firstPoule = $planning->getPoule(1);
        $replacedPlace = $firstPoule->getPlace(5);
        $replacedByPlace = $firstPoule->getPlace(1);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE,
            $planning->createFirstBatch(),
            $replacedPlace,
            $replacedByPlace
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validator = new GameAssignmentValidator($planning);
        self::expectException(Exception::class);
        $validator->validate();
    }

    public function testValidate()
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, null, null, Input::SELFREFEREE_SAMEPOULE)
        );

        $validator = new GameAssignmentValidator($planning);
        self::expectNotToPerformAssertions();
        $validator->validate();
    }
}
