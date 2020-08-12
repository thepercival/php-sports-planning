<?php


namespace SportsPlanning\Tests\Batch;

use SportsPlanning\Batch\PouleCounter;
use SportsPlanning\Batch\RefereePlacePredicter;
use SportsPlanning\Input;
use SportsPlanning\Structure\Service as StructureService;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testCalculations()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3] )
        );

        $pouleOne = $planning->getPoule(1);
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