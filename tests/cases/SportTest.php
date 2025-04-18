<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportVariants\Persist\SportPersistVariant;
use SportsPlanning\Sports\Plannable\PlannableSport;
use SportsPlanning\TestHelper\PlanningCreator;

class SportTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $input = $this->createInput([3]);
        $dbSportVariant = new SportPersistVariant(
            GameMode::Against,
            1,
            1,
            0,
            1,
            0
        );
        $sport = new PlannableSport($input, $dbSportVariant);
        self::assertSame($input, $sport->getInput());
        self::assertSame(2, $sport->getNumber());
        self::assertSame(1, $sport->getNrOfHomePlaces());
        self::assertSame(1, $sport->getNrOfAwayPlaces());
        self::assertSame(0, $sport->getNrOfGamePlaces());
        self::assertSame(1, $sport->getNrOfCycles());
        self::assertSame(0, $sport->getNrOfGamesPerPlace());
    }
}
