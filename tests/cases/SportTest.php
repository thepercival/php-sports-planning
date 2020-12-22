<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Sport;
use SportsPlanning\TestHelper\PlanningCreator;

class SportTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct()
    {
        $planning = $this->createPlanning($this->createInput( [3] ) );
        $sport = new Sport( $planning, 1, 2 );
        self::assertSame($planning, $sport->getPlanning() );
        self::assertSame(1, $sport->getNumber() );
        self::assertSame(2, $sport->getNrOfGamePlaces() );
    }
}
