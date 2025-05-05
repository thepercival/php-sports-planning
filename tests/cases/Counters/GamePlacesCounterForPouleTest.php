<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

class GamePlacesCounterForPouleTest extends TestCase
{
    use PlanningCreator;
    public function testCountSmallerThanZero(): void
    {
        self::expectException(\Exception::class);
        $poule = $this->getPoule();
        new GamePlacesCounterForPoule($poule,0, -1);
    }
    public function testGetPoule(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,0);
        self::assertSame($poule, $counter->getPoule());
    }

    public function testGetNrOfPlacesAssignedWithRefereePlace(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(6, $counter->getNrOfPlacesAssigned(1));
    }

    public function testReset(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(0, $counter->reset()->getNrOfGames());
        self::assertSame(0, $counter->reset()->getNrOfPlacesAssigned());
    }

    public function testAdd(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(4, $counter->add(0, 2)->getNrOfGames());
        self::assertSame(6, $counter->add(2, 0)->getNrOfPlacesAssigned());
    }

    public function testRemove(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(0, $counter->remove(0, 2)->getNrOfGames());
        self::assertSame(3, $counter->remove(1, 0)->getNrOfPlacesAssigned());
    }

    private function getPoule(): Poule
    {
        $input = new Input( new Input\Configuration(
            new PouleStructure(3),
            [$this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(1)],
            new PlanningRefereeInfo(),
            false
        ));
        return $input->getFirstPoule();
    }

}
