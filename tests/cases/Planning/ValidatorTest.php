<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionObject;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Batch;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\GameGenerator;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
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
        $planning = new Planning($this->createInput([3,3]), new SportRange(1, 1), 1);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::NO_GAMES, $validity & PlanningValidator::NO_GAMES);
    }

    public function testHasEmptyGamePlace(): void
    {
        $sportVariant = $this->getAgainstSportVariantWithFields(2, 2, 2, 0, 3);
        $planning = $this->createPlanning($this->createInput([5], [$sportVariant]));
        $firstGame = $planning->getAgainstGames()->first();
        self::assertNotFalse($firstGame);
        $firstGame->getPlaces()->clear();

        //(new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_PLACE, $validity & PlanningValidator::EMPTY_PLACE);
    }

    public function testHasEmptyGameRefereePlace(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SAMEPOULE)
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // --------- BEGIN EDITING --------------
        /** @var AgainstGame $firstGame */
        $firstGame = $planning->getAgainstGames()->first();
        $firstGame->setRefereePlace(null);
//        $firstBatch = $planning->createFirstBatch();
//        $firstBatch->removeAsReferee( $firstGame->getRefereePlace()/*, $firstGame*/ );
        // --------- BEGIN EDITING --------------
        //(new PlanningOutput())->outputWithGames($planning, true);

        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::EMPTY_REFEREEPLACE,
            $validity & PlanningValidator::EMPTY_REFEREEPLACE
        );
    }

    public function testEmptyGameReferee(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([5])
        );

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getAgainstGames()->first();
        $planningGame->emptyReferee();

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::EMPTY_REFEREE, $validity & PlanningValidator::EMPTY_REFEREE);
    }

    public function testAllPlacesSameNrOfGames(): void
    {
        $planning = new Planning($this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, 0), new SportRange(1, 1), 1);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);

        $planningValidator = new PlanningValidator();

        /** @var AgainstGame $planningGame */
        $planningGame = $planning->getAgainstGames()->first();
        $planning->getAgainstGames()->removeElement($planningGame);

        //        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(PlanningValidator::UNEQUAL_GAME_WITH_AGAINST, $planningValidator->validate($planning));
    }

    public function testGamesInARow(): void
    {
        $planning = $this->createPlanning($this->createInput([4]), null);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

//        (new PlanningOutput())->outputWithGames($planning, true);

        // ---------------- MAKE INVALID --------------------- //
        $refObject   = new ReflectionObject($planning);
        $refProperty = $refObject->getProperty('maxNrOfGamesInARow');
        $refProperty->setAccessible(true);
        $refProperty->setValue($planning, 1);
        // ---------------- MAKE INVALID --------------------- //

//        (new PlanningOutput())->outputWithGames($planning, true);


        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::TOO_MANY_GAMES_IN_A_ROW,
            $validity & PlanningValidator::TOO_MANY_GAMES_IN_A_ROW
        );
    }

    public function testGameUnequalHomeAway(): void
    {
        $planning = $this->createPlanning($this->createInput([2]));

        $planningGame = $planning->getAgainstGames()->first();
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
        $planning = $this->createPlanning($this->createInput([5]), new SportRange(2, 2));

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        $newFieldNr = $field->getNumber() === 1 ? 2 : 1;
        $planningGame->setField($planning->getInput()->getSport(1)->getField($newFieldNr));

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
            $this->createInput([4]),
            new SportRange(2, 2)
        );

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $referee = $planningGame->getReferee();
        self::assertNotNull($referee);
        $newRefereeNr = $referee->getNumber() === 1 ? 2 : 1;
        $planningGame->setReferee($planning->getInput()->getReferee($newRefereeNr));

        // (new PlanningOutput())->outputWithGames($planning, true);

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
            $this->createInput([5])
        );

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    public function testValidateNrOfGamesPerField(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(3);
        $planning = $this->createPlanning($this->createInput([4], [$sportVariantWithFields]));

        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $field = $planningGame->getField();
        $newFieldNr = $field->getNumber() === 3 ? 1 : 3;
        $planningGame->setField($planning->getInput()->getSport(1)->getField($newFieldNr));

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
            $this->createInput([5], null, GamePlaceStrategy::EquallyAssigned, 3)
        );

//        $planningOutput = new PlanningOutput();
//        $planningOutput->output($planning, true);

        $batch = $planning->createFirstBatch();
        self::assertInstanceOf(Batch::class, $batch);
        $this->replaceReferee($batch, $planning->getInput()->getReferee(1), $planning->getInput()->getReferee(2), 2);

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
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput(
                [3,3],
                [$sportVariantWithFields],
                GamePlaceStrategy::EquallyAssigned,
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
            $planning->getInput()->getPoule(1)->getPlace(1),
            $planning->getInput()->getPoule(2)->getPlace(1)
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
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput([5], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SAMEPOULE)
        );

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $refereePlaceService = new RefereePlaceService($planning);
        $refereePlaceService->assign($firstBatch);

        // ----------------- BEGIN EDITING --------------------------
        // (new PlanningOutput())->outputWithGames($planning, true);
        $pouleOne = $planning->getInput()->getPoule(1);
        $gamesPouleOne = $planning->getGamesForPoule($pouleOne);
        $refereePlaceTooMuch = $gamesPouleOne[0]->getRefereePlace();
        $refReplaced = false;
        while (!$refReplaced && $refereePlaceTooMuch !== null) {
            $gamePouleOne = array_pop($gamesPouleOne);
            if (!$gamePouleOne->isParticipating($refereePlaceTooMuch)) {
                $gamePouleOne->setRefereePlace($refereePlaceTooMuch);
                $refReplaced = true;
            }
        }
        // (new PlanningOutput())->outputWithGames($planning, true);
        // ----------------- END EDITING --------------------------

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUALLY_ASSIGNED_REFEREEPLACES,
            $validity & PlanningValidator::UNEQUALLY_ASSIGNED_REFEREEPLACES
        );
    }

    public function testValidResourcesPerRefereePlaceDifferentPouleSizes(): void
    {
        $sportVariantWithFields = $this->getAgainstSportVariantWithFields(1);
        $planning = $this->createPlanning(
            $this->createInput([5,4], [$sportVariantWithFields], GamePlaceStrategy::EquallyAssigned, null, SelfReferee::OTHERPOULES)
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
            $this->createInput([5,4], null, GamePlaceStrategy::EquallyAssigned, 3)
        );

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidator::ALL_INVALID, $planning);
        self::assertCount(14, $descriptions);

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof Batch);
        $this->replaceReferee($firstBatch, $planning->getInput()->getReferee(3), $planning->getInput()->getReferee(1));

//        $planningOutput = new PlanningOutput();
//        $planningOutput->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $planningValidator->validate($planning);
        $descriptions = $planningValidator->getValidityDescriptions(PlanningValidator::ALL_INVALID, $planning);
        self::assertCount(14, $descriptions);
    }

    public function testNrOfHomeAwayH2H2(): void
    {
        $sportVariant = new SportVariantWithFields($this->getAgainstSportVariant(1, 1, 2), 2);
        $planning = new Planning($this->createInput([3], [$sportVariant], GamePlaceStrategy::EquallyAssigned, 0), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        // ---------------- MAKE INVALID --------------------- //
        $planningGame = $planning->getAgainstGames()->first();
        self::assertInstanceOf(AgainstGame::class, $planningGame);
        $firstHomeGamePlace = $planningGame->getSidePlaces(AgainstSide::HOME)->first();
        $firstAwayGamePlace = $planningGame->getSidePlaces(AgainstSide::AWAY)->first();
        self::assertInstanceOf(AgainstGamePlace::class, $firstHomeGamePlace);
        self::assertInstanceOf(AgainstGamePlace::class, $firstAwayGamePlace);
        $planningGame->getPlaces()->removeElement($firstHomeGamePlace);
        $planningGame->getPlaces()->removeElement($firstAwayGamePlace);
        new AgainstGamePlace($planningGame, $firstAwayGamePlace->getPlace(), AgainstSide::HOME);
        new AgainstGamePlace($planningGame, $firstHomeGamePlace->getPlace(), AgainstSide::AWAY);
        // ---------------- MAKE INVALID --------------------- //

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();

        $validity = $planningValidator->validate($planning);
        self::assertSame(
            PlanningValidator::UNEQUAL_PLACE_NROFHOMESIDES,
            $validity & PlanningValidator::UNEQUAL_PLACE_NROFHOMESIDES
        );
    }

    public function test6Places2FieldsMax2GamesInARow(): void
    {
        $sportVariant = new SportVariantWithFields($this->getAgainstSportVariant(1, 1, 1), 2);
        $planning = new Planning($this->createInput([6], [$sportVariant], GamePlaceStrategy::EquallyAssigned, 0), new SportRange(2, 2), 2);

        $gameCreator = new GameCreator($this->getLogger());
        // $gameCreator->disableThrowOnTimeout();
        $gameCreator->createAssignedGames($planning);

        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(Planning::STATE_SUCCEEDED, $planning->getState());

        $planningValidator = new PlanningValidator();

        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}
