<?php

namespace SportsPlanning\Tests\Planning;

use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use SportsPlanning\Batch;
use Voetbal\Field;
use SportsPlanning;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;
use Voetbal\Structure\Service as StructureService;
use SportsPlanning\Validator as PlanningValidator;
use SportsPlanning\Game;
use Voetbal\Game as GameBase;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Field as PlanningField;
use Voetbal\Referee;
use Exception;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator, PlanningReplacer;

    public function testHasEnoughTotalNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $planning->getPoule(2)->getGames()->clear();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::NO_GAMES, $validity & PlanningValidator::NO_GAMES);
    }

    public function testHasEmptyGamePlace()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5, 1);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $firstGame = $planning->getPoule(1)->getGames()->first();
        $firstGame->getPlaces()->clear();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_PLACE, $validity & PlanningValidator::EMPTY_PLACE);
    }

    public function testHasEmptyGameField()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5, 1);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        /** @var Game $firstGame */
        $firstGame = $planning->getPoule(1)->getGames()->first();
        $firstGame->emptyField();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_FIELD, $validity & PlanningValidator::EMPTY_FIELD);
    }

    public function testHasEmptyGameRefereePlace()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $roundNumber->getPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->createFirstBatch());

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $planningGame->emptyRefereePlace();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::EMPTY_REFEREEPLACE,
            $validity & PlanningValidator::EMPTY_REFEREEPLACE
        );
    }

    public function testEmptyGameReferee()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $planningGame->emptyReferee();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_REFEREE, $validity & PlanningValidator::EMPTY_REFEREE);
    }

    public function testAllPlacesSameNrOfGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        $planningGames = $planning->getPoule(1)->getGames();
        $removed = $planningGames->first();
        $planningGames->removeElement($removed);
        self::assertSame(PlanningValidator::NOT_EQUALLY_ASSIGNED_PLACES, $planningValidator->validate($planning));
    }

    public function testGamesInARow()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        $planning->setMaxNrOfGamesInARow(3);
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::TOO_MANY_GAMES_IN_A_ROW,
            $validity & PlanningValidator::TOO_MANY_GAMES_IN_A_ROW
        );
    }

    public function testBatchMultiplePlaces()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $firstHomeGamePlace = $planningGame->getPlaces(GameBase::HOME)->first();
        // $firstHomePlace = $firstHomeGamePlace->getPlace();
        // $firstAwayPlace = $planningGame->getPlaces(Game::AWAY)->first()->getPlace();
        $planningGame->getPlaces()->add($firstHomeGamePlace);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUAL_GAME_HOME_AWAY,
            $validity & PlanningValidator::UNEQUAL_GAME_HOME_AWAY
        );
    }

    public function testBatchMultipleFields()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newFieldNr = $planningGame->getField()->getNumber() === 1 ? 2 : 1;
        $planningGame->setField($planning->getField($newFieldNr));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH,
            PlanningValidator::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH & $validity
        );
    }


    public function testBatchMultipleReferees()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newRefereeNr = $planningGame->getReferee()->getNumber() === 1 ? 2 : 1;
        $planningGame->setReferee($planning->getReferee($newRefereeNr));
        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH,
            PlanningValidator::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH & $validity
        );
    }

    public function testValidResourcesPerBatch()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidateNrOfGamesPerField()
    {
        $competition = $this->createCompetition();

        new Field($competition->getFirstSportConfig());

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 4);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newFieldNr = $planningGame->getField()->getNumber() === 3 ? 1 : 3;
        $planningGame->setField($planning->getField($newFieldNr));

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUALLY_ASSIGNED_FIELDS,
            $validity & PlanningValidator::UNEQUALLY_ASSIGNED_FIELDS
        );
    }

    public function testValidResourcesPerReferee()
    {
        $competition = $this->createCompetition();

        new Referee($competition);

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $this->replaceReferee($planning->createFirstBatch(), $planning->getReferee(1), $planning->getReferee(2), 2);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUALLY_ASSIGNED_REFEREES,
            $validity & PlanningValidator::UNEQUALLY_ASSIGNED_REFEREES
        );
    }

    protected function replaceReferee(
        Batch $batch,
        PlanningReferee $fromReferee,
        PlanningReferee $toReferee,
        int $amount = 1
    ) {
        $amountReplaced = 0;
        /** @var Game $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() !== $fromReferee || $this->batchHasReferee($batch, $toReferee)) {
                continue;
            }
            $game->setReferee($toReferee);
            if (++$amountReplaced === $amount) {
                return;
            }
        }
        if ($batch->hasNext()) {
            $this->replaceReferee($batch->getNext(), $fromReferee, $toReferee, $amount);
        }
    }

    protected function batchHasReferee(Batch $batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() === $referee) {
                return true;
            }
        }
        return false;
    }

    public function testInvalidAssignedRefereePlaceSamePoule()
    {
        $competition = $this->createCompetition();

        // remove field
        $competition->getFirstSportConfig()->getFields()->removeElement(
            $competition->getFirstSportConfig()->getFields()->first()
        );

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $roundNumber->getPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->createFirstBatch());

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() !== Input::SELFREFEREE_SAMEPOULE,
            $planning->createFirstBatch(),
            $planning->getPoule(1)->getPlace(1),
            $planning->getPoule(2)->getPlace(1)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::INVALID_ASSIGNED_REFEREEPLACE,
            $validity & PlanningValidator::INVALID_ASSIGNED_REFEREEPLACE
        );
    }

    public function testValidResourcesPerRefereePlace()
    {
        $competition = $this->createCompetition();

        // remove field
        $competition->getFirstSportConfig()->getFields()->removeElement(
            $competition->getFirstSportConfig()->getFields()->first()
        );

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 5);

        $roundNumber = $structure->getFirstRoundNumber();

        $roundNumber->getPlanningConfig()->setSelfReferee(Input::SELFREFEREE_SAMEPOULE);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->createFirstBatch());

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE,
            $planning->createFirstBatch(),
            $planning->getPoule(1)->getPlace(1),
            $planning->getPoule(1)->getPlace(4)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUALLY_ASSIGNED_REFEREEPLACES,
            $validity & PlanningValidator::UNEQUALLY_ASSIGNED_REFEREEPLACES
        );
    }

    public function testValidResourcesPerRefereePlaceDifferentPouleSizes()
    {
        $competition = $this->createCompetition();

        // remove field
        $competition->getFirstSportConfig()->getFields()->removeElement(
            $competition->getFirstSportConfig()->getFields()->first()
        );

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $roundNumber->getPlanningConfig()->setSelfReferee(Input::SELFREFEREE_OTHERPOULES);
        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->createFirstBatch());

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidityDescriptions()
    {
        $competition = $this->createCompetition();
        new Referee($competition);

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 9, 2);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidator::ALL_INVALID);
        self::assertCount(13, $descriptions);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $this->replaceReferee($planning->createFirstBatch(), $planning->getReferee(3), $planning->getReferee(1));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidator::ALL_INVALID);
        self::assertCount(13, $descriptions);
    }
}
