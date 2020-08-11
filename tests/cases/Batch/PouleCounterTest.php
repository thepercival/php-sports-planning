<?php


namespace SportsPlanning\Tests\Batch;

use SportsPlanning\Batch\PouleCounter;
use SportsPlanning\Batch\RefereePlacePredicter;
use SportsPlanning\Input;
use Voetbal\Structure\Service as StructureService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;

class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator, PlanningReplacer;

    public function testCalculations()
    {
        $competition = $this->createCompetition();

        $structureService = new StructureService([]);
        $structure = $structureService->create($competition, 3);

        $roundNumber = $structure->getFirstRoundNumber();

        $options = [];
        $planning = $this->createPlanning($roundNumber, $options);

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