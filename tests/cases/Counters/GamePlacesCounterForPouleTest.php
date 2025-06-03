<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\Poule;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
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

    public function testCalculateNrOfAssignedGamePlacesWithRefereePlace(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(6, $counter->calculateNrOfAssignedGamePlaces(1));
    }

    public function testReset(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(0, $counter->reset()->getNrOfGames());
        self::assertSame(0, $counter->reset()->calculateNrOfAssignedGamePlaces());
    }

    public function testAdd(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(4, $counter->add(0, 2)->getNrOfGames());
        self::assertSame(6, $counter->add(2, 0)->calculateNrOfAssignedGamePlaces());
    }

    public function testRemove(): void
    {
        $poule = $this->getPoule();
        $counter = new GamePlacesCounterForPoule($poule,4, 2);
        self::assertSame(0, $counter->remove(0, 2)->getNrOfGames());
        self::assertSame(3, $counter->remove(1, 0)->calculateNrOfAssignedGamePlaces());
    }

    private function getPoule(): Poule
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $input = new PlanningOrchestration( new PlanningConfiguration(
            new PouleStructure(3),
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo(),
            false
        ));
        $planning = new Planning($input, new SportRange(1,1),2);
        return $planning->getFirstPoule();
    }

}
