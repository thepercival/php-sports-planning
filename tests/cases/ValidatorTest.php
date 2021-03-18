<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportConfig;
use SportsHelpers\SportRange;
use SportsPlanning\Batch;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\SelfReferee;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Referee as PlanningReferee;

class ValidatorTest extends TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testHasEnoughTotalNrOfGames(): void
    {
        $planning = $this->createPlanning($this->createInputNew([3,3]));
        $planning->getPoule(2)->getAgainstGames()->clear();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::NO_GAMES, $validity & PlanningValidator::NO_GAMES);
    }

    public function testHasEmptyGamePlace(): void
    {
        $planning = $this->createPlanning($this->createInputNew([5]));
        $firstGame = $planning->getPoule(1)->getAgainstGames()->first();
        self::assertNotFalse($firstGame);
        $firstGame->getPlaces()->clear();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_PLACE, $validity & PlanningValidator::EMPTY_PLACE);
    }

    public function testHasEmptyGameField(): void
    {
        $planning = $this->createPlanning($this->createInputNew([5]));
        /** @var AgainstGame $firstGame */
        $firstGame = $planning->getPoule(1)->getAgainstGames()->first();
        $firstGame->emptyField();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_FIELD, $validity & PlanningValidator::EMPTY_FIELD);
    }

    public function testHasEmptyGameRefereePlace(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5], null, null, SelfReferee::SAMEPOULE)
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        /** @var AgainstGame $firstGame */
        $firstGame = $planning->getPoule(1)->getAgainstGames()->first();
        $firstGame->setRefereePlace(null);
//        $firstBatch = $planning->createFirstBatch();
//        $firstBatch->removeAsReferee( $firstGame->getRefereePlace()/*, $firstGame*/ );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::EMPTY_REFEREEPLACE,
            $validity & PlanningValidator::EMPTY_REFEREEPLACE
        );
    }

    public function testEmptyGameReferee(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5])
        );

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getPoule(1)->getAgainstGames()->first();
        $planningGame->emptyReferee();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_REFEREE, $validity & PlanningValidator::EMPTY_REFEREE);
    }

    public function testAllPlacesSameNrOfGames(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5])
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getPoule(1)->getAgainstGames()->first();
        $planning->getPoule(1)->getAgainstGames()->removeElement($planningGame);
        self::assertSame(PlanningValidator::NOT_EQUALLY_ASSIGNED_PLACES, $planningValidator->validate($planning));
    }

    public function testGamesInARow(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5])
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

    public function testGameUnequalHomeAway(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([2])
        );

        $planningGame = $planning->getPoule(1)->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $firstHomeGamePlace = $planningGame->getSidePlaces(AgainstSide::HOME)->first();
        // $firstHomePlace = $firstHomeGamePlace->getPlace();
        // $firstAwayPlace = $planningGame->getPlaces(Game::AWAY)->first()->getPlace();
        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
        $planningGame->getPlaces()->add($firstHomeGamePlace);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUAL_GAME_HOME_AWAY,
            $validity & PlanningValidator::UNEQUAL_GAME_HOME_AWAY
        );
    }

    public function testBatchMultipleFields(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5]),
            new SportRange(2, 2)
        );

        $planningGame = $planning->getPoule(1)->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        self::assertNotNull($field);
        $newFieldNr = $field->getNumber() === 1 ? 2 : 1;
        $planningGame->setField($planning->getSport(1)->getField($newFieldNr));

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH,
            PlanningValidator::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH & $validity
        );
    }


    public function testBatchMultipleReferees(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5]),
            new SportRange(2, 2)
        );

        $planningGame = $planning->getPoule(1)->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $referee = $planningGame->getReferee();
        self::assertNotNull($referee);
        $newRefereeNr = -$referee->getNumber() === 1 ? 2 : 1;
        $planningGame->setReferee($planning->getReferee($newRefereeNr));
        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH,
            PlanningValidator::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH & $validity
        );
    }

    public function testValidResourcesPerBatch(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5])
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidateNrOfGamesPerField(): void
    {
        $sportConfig = new SportConfig(GameMode::AGAINST, 2, 3, 1);
        $planning = $this->createPlanning($this->createInputNew([4], [$sportConfig]));

        $planningGame = $planning->getPoule(1)->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        self::assertNotNull($field);
        $newFieldNr = $field->getNumber() === 3 ? 1 : 3;
        $planningGame->setField($planning->getSport(1)->getField($newFieldNr));

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUALLY_ASSIGNED_FIELDS,
            $validity & PlanningValidator::UNEQUALLY_ASSIGNED_FIELDS
        );
    }

    public function testValidResourcesPerReferee(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5], null, 3)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $batch = $planning->createFirstBatch();
        self::assertInstanceOf(Batch::class, $batch);
        $this->replaceReferee($batch, $planning->getReferee(1), $planning->getReferee(2), 2);

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
    ): void {
        $amountReplaced = 0;
        /** @var AgainstGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() !== $fromReferee || $this->batchHasReferee($batch, $toReferee)) {
                continue;
            }
            $game->setReferee($toReferee);
            if (++$amountReplaced === $amount) {
                return;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->replaceReferee($nextBatch, $fromReferee, $toReferee, $amount);
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

    public function testInvalidAssignedRefereePlaceSamePoule(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew(
                [3,3],
                [new SportConfig(GameMode::AGAINST, 2, 1, 1)],
                null,
                SelfReferee::SAMEPOULE
            )
        );

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $this->replaceRefereePlace(
            $planning->getInput()->getSelfReferee() !== SelfReferee::SAMEPOULE,
            $firstBatch,
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

    public function testValidResourcesPerRefereePlace(): void
    {
        $sportConfigs = [new SportConfig(GameMode::AGAINST, 2, 1, 1)];
        $planning = $this->createPlanning(
            $this->createInputNew([5], $sportConfigs, null, SelfReferee::SAMEPOULE)
        );

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstGame = $planning->getPoule(1)->getGames()[0];
        $secondGame = $planning->getPoule(1)->getGames()[1];
        $firstGame->setRefereePlace($secondGame->getRefereePlace());
//        $this->replaceRefereePlace(
//            $planning->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE,
//            $planning->createFirstBatch(),
//            $planning->getPoule(1)->getPlace(1),
//            $planning->getPoule(1)->getPlace(4)
//        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUALLY_ASSIGNED_REFEREEPLACES,
            $validity & PlanningValidator::UNEQUALLY_ASSIGNED_REFEREEPLACES
        );
    }

    public function testValidResourcesPerRefereePlaceDifferentPouleSizes(): void
    {
        $sportConfigs = [new SportConfig(GameMode::AGAINST, 2, 1, 1)];
        $planning = $this->createPlanning(
            $this->createInputNew([5,4], $sportConfigs, null, SelfReferee::OTHERPOULES)
        );
        $refereePlaceService = new RefereePlaceService($planning);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $refereePlaceService->assign($firstBatch);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidityDescriptions(): void
    {
        $planning = $this->createPlanning(
            $this->createInputNew([5,4], null, 3)
        );

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidator::ALL_INVALID, $planning);
        self::assertCount(13, $descriptions);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof Batch);
        $this->replaceReferee($firstBatch, $planning->getReferee(3), $planning->getReferee(1));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidator::ALL_INVALID, $planning);
        self::assertCount(13, $descriptions);
    }
}
