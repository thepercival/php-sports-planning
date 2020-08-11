<?php


namespace SportsPlanning\Tests\Planning\Resource\GameCounter;

use SportsPlanning\Resource\GameCounter\Place as PlaceCounter;
use SportsPlanning\Resource\GameCounter\Unequal;
use Voetbal\Structure\Service as StructureService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;

class UnequalTest extends \PHPUnit\Framework\TestCase
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

        $placeOne = $planning->getPoule(1)->getPlace(1);
        $placeTwo = $planning->getPoule(1)->getPlace(2);
        $gameCounterPlaceOne = new PlaceCounter($placeOne);
        $gameCounterPlaceTwo = new PlaceCounter($placeTwo);

        $unequal = new Unequal(1, [$gameCounterPlaceOne], 3, [$gameCounterPlaceTwo]);
        $unequal->setPouleNr(1);
        self::assertSame(1, $unequal->getPouleNr());

        self::assertSame(1, $unequal->getMinNrOfGames());
        self::assertSame(3, $unequal->getMaxNrOfGames());
        self::assertSame(2, $unequal->getDifference());

        self::assertSame([$gameCounterPlaceOne], $unequal->getMinGameCounters());
        self::assertSame([$gameCounterPlaceTwo], $unequal->getMaxGameCounters());
    }

}