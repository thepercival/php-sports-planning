<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Batch;

use SportsPlanning\PouleCounter;
use SportsPlanning\Batch\Predicter;
use SportsPlanning\Input;
use SportsPlanning\Structure\Service as StructureService;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

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
