<?php

namespace SportsPlanning\Tests\Planning;

use Cassandra\Date;
use League\Period\Period;
use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use SportsPlanning\Assigner as PlanningConvertService;
use SportsPlanning\Batch;
use SportsPlanning\Config\Service as PlanningConfigService;
use SportsPlanning;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\ScheduleService;
use Voetbal\Qualify\Group;
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

class ScheduleServiceTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator, PlanningReplacer;

    public function testValidDateTimes()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $scheduleService = new ScheduleService();
        $scheduleService->rescheduleGames($firstRoundNumber);

        self::assertEquals($competitionStartDateTime, $firstRoundNumber->getGames()[0]->getStartDateTime());
        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodBeforeFirstGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $blockedPeriod = new Period(
            $competitionStartDateTime->modify("-1 minutes"),
            $competitionStartDateTime->modify("+" . (40 - 1) . " minutes")
        );
        $scheduleService = new ScheduleService($blockedPeriod);
        $scheduleService->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40 - 1);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodBeforeSecondBatchGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(GameBase::ORDER_BY_BATCH)[2];

        $blockedPeriod = new Period(
            $secondBatchGame->getStartDateTime()->modify("-1 minutes"),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $scheduleService = new ScheduleService($blockedPeriod);
        $scheduleService->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodDuringSecondBatchGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(GameBase::ORDER_BY_BATCH)[2];

        $blockedPeriod = new Period(
            $secondBatchGame->getStartDateTime()->modify("+1 minutes"),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $scheduleService = new ScheduleService($blockedPeriod);
        $scheduleService->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodAtStartSecondBatchGame()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondBatchGame = $firstRoundNumber->getGames(GameBase::ORDER_BY_BATCH)[2];

        $blockedPeriod = new Period(
            clone $secondBatchGame->getStartDateTime(),
            $secondBatchGame->getStartDateTime()->modify("+40 minutes")
        );
        $scheduleService = new ScheduleService($blockedPeriod);
        $scheduleService->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testBlockedPeriodBetweenRounds()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $competitionStartDateTime = $competition->getStartDateTime();

        $secondRoundNumberStartDateTimeTmp = $this->getStartSecond($competitionStartDateTime);

        $blockedPeriod = new Period(
            $secondRoundNumberStartDateTimeTmp->modify("-1 minutes"),
            $secondRoundNumberStartDateTimeTmp->modify("+40 minutes")
        );
        $scheduleService = new ScheduleService($blockedPeriod);
        $scheduleService->rescheduleGames($firstRoundNumber);

        $secondRoundNumberStartDateTime = $this->getStartSecond($competitionStartDateTime, 40);
        self::assertEquals($secondRoundNumberStartDateTime, $secondRoundNumber->getGames()[0]->getStartDateTime());
    }

    public function testRoundNumberNoGames()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 6, 2);

        $structureService->addQualifiers($structure->getRootRound(), Group::WINNERS, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $secondRoundNumber = $firstRoundNumber->getNext();

        $options = [];
        $firstRoundNumberPlanning = $this->createPlanning($firstRoundNumber, $options);
        $secondRoundNumberPlanning = $this->createPlanning($secondRoundNumber, $options);

        $planningConvertService = new PlanningConvertService(new ScheduleService());
        $planningConvertService->createGames($firstRoundNumber, $firstRoundNumberPlanning);
        $planningConvertService->createGames($secondRoundNumber, $secondRoundNumberPlanning);

        $secondRoundNumber->getPoules()[0]->getGames()->clear();
//        foreach( $firstRoundNumber->getGames( GameBase::ORDER_BY_BATCH ) as $game ) {
//            (new \Voetbal\Output\Game())->output($game);
//        }

        $scheduleService = new ScheduleService();
        self::expectException(Exception::class);
        $scheduleService->rescheduleGames($firstRoundNumber);
    }

    protected function getStartSecond(\DateTimeImmutable $startFirst, int $delta = 0): \DateTimeImmutable
    {
        $planningConfigService = new PlanningConfigService();
        $addMinutes = 3 * $planningConfigService->getDefaultMinutesPerGame();
        $addMinutes += 2 * $planningConfigService->getDefaultMinutesBetweenGames();
        $addMinutes += $planningConfigService->getDefaultMinutesAfter();
        return $startFirst->modify("+" . ($addMinutes + $delta) . " minutes");
    }
}
