<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Poule;

use SportsPlanning\Poule\PouleCounter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

    public function testCalculations(): void
    {
        $planning = $this->createPlanning($this->createInput([3]));

        $pouleOne = $planning->getInput()->getPoule(1);
        $pouleCounter = new PouleCounter($pouleOne);

        $nrOfPlacesAssigned = 3;
        $pouleCounter->add($nrOfPlacesAssigned);

        self::assertSame($nrOfPlacesAssigned, $pouleCounter->getNrOfPlacesAssigned());
        self::assertSame(1, $pouleCounter->getNrOfGames());

        $pouleCounter->reset();
        self::assertSame(0, $pouleCounter->getNrOfPlacesAssigned());
        self::assertSame(0, $pouleCounter->getNrOfGames());

        self::assertSame($pouleOne, $pouleCounter->getPoule());
    }
}
