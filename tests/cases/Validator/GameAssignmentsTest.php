<?php

namespace SportsPlanning\Tests\Planning\Validator;

use \Exception;
use SportsPlanning\Input;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Validator\GameAssignments as GameAssignmentValidator;
use Voetbal\Referee;
use Voetbal\Structure\Service as StructureService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;
use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Field;

class GameAssignmentsTest extends \PHPUnit\Framework\TestCase
{

    use CompetitionCreator, PlanningCreator, PlanningReplacer;

    public function testGetCountersFields()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

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
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

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
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $roundNumber->getValidPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

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
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $roundNumber->getValidPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

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
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9, 2);

        $roundNumber = $structure->getFirstRoundNumber();
        $roundNumber->getValidPlanningConfig()->setSelfReferee(Input::SELFREFEREE_OTHERPOULES);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $secondPoule = $planning->getPoule(2);
        $replacedPlace = $secondPoule->getPlace(3);
        $replacedByPlace = $secondPoule->getPlace(4);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE,
            $planning->createFirstBatch(),
            $replacedPlace,
            $replacedByPlace
        );

        $validator = new GameAssignmentValidator($planning);
        $unequals = $validator->getRefereePlaceUnequals();

        self::assertCount(1, $unequals);
    }

    public function testValidateUnequalFields()
    {
        $competition = $this->createCompetition();
        new Field($competition->getFirstSportConfig());

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningGames = $planning->getPoule(1)->getGames();
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
        $competition = $this->createCompetition();
        new Referee($competition);

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningGames = $planning->getPoule(1)->getGames();
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
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $roundNumber->getValidPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

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
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();
        $roundNumber->getValidPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $validator = new GameAssignmentValidator($planning);
        self::assertNull($validator->validate());
    }
}
