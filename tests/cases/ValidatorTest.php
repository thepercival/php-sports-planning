<?php

namespace SportsPlanning\Tests;

use SportsPlanning\Batch;
use SportsPlanning\Field;
use SportsHelpers\Range;
use SportsPlanning;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Game;
use SportsPlanning\Game as GameBase;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Referee;
use \Exception;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testHasEnoughTotalNrOfGames()
    {
        $planning = $this->createPlanning($this->createInput( [3,3] ) );
        $planning->getPoule(2)->getGames()->clear();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::NO_GAMES, $validity & PlanningValidator::NO_GAMES);
    }

    public function testHasEmptyGamePlace()
    {
        $planning = $this->createPlanning($this->createInput( [5] ) );
        $firstGame = $planning->getPoule(1)->getGames()->first();
        $firstGame->getPlaces()->clear();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_PLACE, $validity & PlanningValidator::EMPTY_PLACE);
    }

    public function testHasEmptyGameField()
    {
        $planning = $this->createPlanning($this->createInput( [5] ) );
        /** @var Game $firstGame */
        $firstGame = $planning->getPoule(1)->getGames()->first();
        $firstGame->emptyField();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_FIELD, $validity & PlanningValidator::EMPTY_FIELD);
    }

    public function testHasEmptyGameRefereePlace()
    {
        $planning = $this->createPlanning(
            $this->createInput( [5], null, null, null, Input::SELFREFEREE_SAMEPOULE )
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        /** @var Game $firstGame */
        $firstGame = $planning->getPoule(1)->getGames()->first();
        $firstBatch = $planning->createFirstBatch();
        $firstBatch->removeAsReferee( $firstGame->getRefereePlace()/*, $firstGame*/ );
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::EMPTY_REFEREEPLACE,
            $validity & PlanningValidator::EMPTY_REFEREEPLACE
        );
    }

    public function testEmptyGameReferee()
    {
        $planning = $this->createPlanning(
            $this->createInput( [5] )
        );

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $planningGame->emptyReferee();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_REFEREE, $validity & PlanningValidator::EMPTY_REFEREE);
    }

    public function testAllPlacesSameNrOfGames()
    {
        $planning = $this->createPlanning(
            $this->createInput( [5] )
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [5] )
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        $planning->setMaxNrOfGamesInARow(1);
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::TOO_MANY_GAMES_IN_A_ROW,
            $validity & PlanningValidator::TOO_MANY_GAMES_IN_A_ROW
        );
    }

    public function testBatchMultiplePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [2] )
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [5] ), new Range(2, 2)
        );

        /** @var Game $planningGame */
        $planningGame = $planning->getPoule(1)->getGames()->first();
        $newFieldNr = $planningGame->getField()->getNumber() === 1 ? 2 : 1;
        $planningGame->setField($planning->getField($newFieldNr));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH,
            PlanningValidator::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH & $validity
        );
    }


    public function testBatchMultipleReferees()
    {
        $planning = $this->createPlanning(
            $this->createInput( [5] ), new Range(2, 2)
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [5] )
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidateNrOfGamesPerField()
    {
        $planning = $this->createPlanning(
            $this->createInput( [4], $this->createSportConfig(3) )
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [5], null, 3)
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [3,3], $this->createSportConfig(1), null, null, Input::SELFREFEREE_SAMEPOULE )
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [5], $this->createSportConfig(1), null, null, Input::SELFREFEREE_SAMEPOULE )
        );

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
        $planning = $this->createPlanning(
            $this->createInput( [5,4], $this->createSportConfig(1), null, null, Input::SELFREFEREE_OTHERPOULES )
        );
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($planning->createFirstBatch());

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidityDescriptions()
    {
        $planning = $this->createPlanning(
            $this->createInput( [5,4], null, 3 )
        );

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
