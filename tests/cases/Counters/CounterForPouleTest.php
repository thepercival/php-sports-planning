<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Counters\CounterForPoule;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

class CounterForPouleTest extends TestCase
{
    use PlanningCreator;

    public function testCountSmallerThanZero(): void
    {
        $poule = $this->getPoule();
        self::expectException(\Exception::class);
        new CounterForPoule($poule, -1);
    }

    public function testGetPoule(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule);
        self::assertSame($poule, $counterForPoule->getPoule());
    }

    public function testIncrement(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule, 1);
        self::assertCount(2, $counterForPoule->increment());
    }

    public function testDecrement(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule, 1);
        self::assertCount(0, $counterForPoule->decrement());
    }

    public function testDecrementException(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule);
        self::expectException(\Exception::class);
        $counterForPoule->decrement();
    }

    public function testToString(): void
    {
        $poule = $this->getPoule();
        $counterForPoule = new CounterForPoule($poule, 2);
        self::assertSame('1 2x', (string)$counterForPoule);
    }

    private function getPoule(): Poule
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 1)
        ];
        $input = new PlanningOrchestration( new \SportsPlanning\PlanningConfiguration(
            new PouleStructure(3),
            $sportsWithNrOfFieldsAndNrOfCycles,
            new PlanningRefereeInfo(),
            false
        ));
        $planning = new Planning($input, new SportRange(1,1),2);
        return $planning->getFirstPoule();
    }
}
