<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule;

use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Output as ScheduleOutput;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;
use SportsPlanning\TestHelper\PlanningCreator;

class CreatorTest extends TestCase
{
    use PlanningCreator;

    public function testH2hAndGpp(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1),
        ];
        self::expectException(Exception::class);
        $this->createInput([2], $sportVariants);
    }

    public function testMaxMargin(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1)
        ];
        $input = $this->createInput([5], $sportVariants);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        self::assertEquals(0, $maxGppMargin);
    }

    public function testGppAndGpp5Places1GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
        ];
        $input = $this->createInput([5], $sportVariants);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        self::assertEquals(4, $this->getNrOfGames($schedule));

//        (new ScheduleOutput($this->getLogger()))->output($schedules);

        self::assertEquals(1, $this->getNrOfGames($schedule, 1));
        self::assertEquals(1, $this->getNrOfGames($schedule, 2));
        self::assertEquals(2, $this->getNrOfGames($schedule, 3));
        self::assertEquals(2, $this->getNrOfGames($schedule, 4));
        self::assertEquals(2, $this->getNrOfGames($schedule, 5));
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    protected function getNrOfGames(Schedule $schedule, int|null $placeNr = null): int
    {
        $nrOfGames = 0;
        foreach ($schedule->getSportSchedules() as $sportSchedule) {
            if ($placeNr === null) {
                $nrOfGames += count($sportSchedule->getGames());
                continue;
            }
            foreach ($sportSchedule->getGames() as $game) {
                foreach ($game->getGamePlaces() as $gamePlace) {
                    if ($gamePlace->getNumber() === $placeNr) {
                        $nrOfGames++;
                    }
                }
            }
        }
        return $nrOfGames;
    }

    public function testGppAndGpp5Places2GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([5], $sportVariants);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        self::assertEquals(7, $this->getNrOfGames($schedule));
//        (new Output($this->getLogger()))->output($schedules);
    }

    public function test5Gpps8Places(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7)
        ];
        $input = $this->createInput([8], $sportVariants);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        self::assertEquals(140, $this->getNrOfGames($schedule));
//        (new Output($this->getLogger()))->output($schedules);
    }

    public function test2Single5Places(): void
    {
        $sportVariants = [
            $this->getSingleSportVariantWithFields(1, 1, 2),
            $this->getSingleSportVariantWithFields(1, 1, 2)
        ];
        $input = $this->createInput([5], $sportVariants);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        foreach ($schedule->getSportSchedules() as $sportSchedule) {
            if ($sportSchedule->getNumber() === 1) {
                $this->checkFirstGamePlace($sportSchedule, 1);
            }
            if ($sportSchedule->getNumber() === 2) {
                $this->checkFirstGamePlace($sportSchedule, 5);
            }
        }
//        (new Output($this->getLogger()))->output($schedules);
    }

    protected function checkFirstGamePlace(SportSchedule $sportSchedule, int $placeNr): void
    {
        $firstGame = $sportSchedule->getGames()->first();
        self::assertNotFalse($firstGame);
        $firstGamePlace = $firstGame->getGamePlaces()->first();
        self::assertNotFalse($firstGamePlace);
        self::assertEquals($placeNr, $firstGamePlace->getNumber());
    }

    public function test3Single5Places(): void
    {
        $sportVariants = [
            $this->getSingleSportVariantWithFields(1, 1, 2),
            $this->getSingleSportVariantWithFields(1, 1, 2),
            $this->getSingleSportVariantWithFields(1, 1, 2)
        ];
        $input = $this->createInput([5], $sportVariants);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $schedule = reset($schedules);
        self::assertNotFalse($schedule);

        foreach ($schedule->getSportSchedules() as $sportSchedule) {
            if ($sportSchedule->getNumber() === 1) {
                $this->checkFirstGamePlace($sportSchedule, 1);
            }
            if ($sportSchedule->getNumber() === 2) {
                $this->checkFirstGamePlace($sportSchedule, 5);
            }
            if ($sportSchedule->getNumber() === 3) {
                $this->checkFirstGamePlace($sportSchedule, 4);
            }
        }
//        (new Output($this->getLogger()))->output($schedules);
    }

    public function test3SportsEqualNrOfAgainst(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1),
            $this->getAgainstGppSportVariantWithFields(1)
        ];

        $input = $this->createInput([4], $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input, 0);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        // (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        foreach( $schedules as $schedule) {
            $sportVariants = array_values($schedule->createSportVariants()->toArray());
            $assignedCounter = new AssignedCounter($schedule->getPoule(), $sportVariants);
            foreach( $schedule->getSportSchedules() as $sportSchedule) {
                $sportVariant = $sportSchedule->createVariant();
                if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                    $homeAways = $sportSchedule->convertGamesToHomeAways();
                    $assignedCounter->assignHomeAways($homeAways);
                }
            }
            self::assertSame(0, $assignedCounter->getAgainstSportAmountDifference() );
        }
    }

    public function test4SportsWith6Places(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1)
        ];

        $input = $this->createInput([6], $sportVariantsWithFields);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input, 1);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        foreach( $schedules as $schedule) {
            $sportVariants = array_values($schedule->createSportVariants()->toArray());
            $assignedCounter = new AssignedCounter($schedule->getPoule(), $sportVariants);
            foreach( $schedule->getSportSchedules() as $sportSchedule) {
                $sportVariant = $sportSchedule->createVariant();
                if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                    $homeAways = $sportSchedule->convertGamesToHomeAways();
                    $assignedCounter->assignHomeAways($homeAways);
                }
            }
            self::assertSame(2, $assignedCounter->getAgainstSportAmountDifference() );
        }
    }

    protected function getWithAssignedDifference(SportSchedule $sportSchedule): int
    {
        $assignedCounter = new AssignedCounter($sportSchedule->getSchedule()->getPoule(),[$sportSchedule->createVariant()]);
        $homeAways = $sportSchedule->convertGamesToHomeAways();
        $assignedCounter->assignHomeAways($homeAways);
        return $assignedCounter->getWithSportAmountDifference();
    }


    protected function checkNotParticipating(SportSchedule $sportSchedule, int $placeNr): void
    {
        self::assertCount(
            0,
            $sportSchedule->getGames()->filter(function (Game $game) use ($placeNr): bool {
                return $game->getGamePlaces()->filter(function (GamePlace $gamePlace) use ($placeNr): bool {
                        return $gamePlace->getNumber() === $placeNr;
                    })->count() > 0;
            })
        );
    }
}
