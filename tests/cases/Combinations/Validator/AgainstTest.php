<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations\Validator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Validator\Against as AgainstValidator;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Planning;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariant = $this->getAgainstH2hSportVariantWithFields(1);
        $input = $this->createInput([2], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test4Places1VS1(): void
    {
        $sportVariant = $this->getAgainstH2hSportVariantWithFields(1);
        $input = $this->createInput([4], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test5Places1VS1(): void
    {
        $sportVariant = $this->getAgainstH2hSportVariantWithFields(1);
        $input = $this->createInput([5], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test6Places1VS1(): void
    {
        $sportVariant = $this->getAgainstH2hSportVariantWithFields(1);
        $input = $this->createInput([6], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

    public function test5Places2VS2(): void
    {
        $sportVariant = $this->getAgainstGppSportVariantWithFields(1, 2, 2, 12);
        $input = $this->createInput([5], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $counter = new AgainstValidator($input->getPoule(1), $input->getSport(1));
        $counter->addGames($planning);
        //echo $counter;

        self::assertTrue($counter->balanced());
    }

//    public function test6Places2VS2(): void
//    {
//        $sportVariant = $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 8);
//        $input = $this->createInput([6], [$sportVariant]);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        $counter = new AgainstAndAgainstCounter($input->getPoule(1), $input->getSport(1));
//        $counter->addGames($planning);
//        echo $counter;
//
//        self::assertTrue($counter->balanced());
//    }
}
