<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Poule;

use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

final class PouleCounterTest extends \PHPUnit\Framework\TestCase
{

    public function testCalculations(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1)
        ];
        $orchestration = new PlanningOrchestration( new PlanningConfiguration(
            new PouleStructure([3]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2),
            false
        ));
        $planning = new Planning($orchestration, new SportRange(1,1),2);

        $pouleOne = $planning->getPoule(1);
        $gamePlacesCounter = new GamePlacesCounterForPoule($pouleOne);

        $nrOfPlacesAssigned = 3;
        $gamePlacesCounter = $gamePlacesCounter->add($nrOfPlacesAssigned);

        self::assertSame($nrOfPlacesAssigned, $gamePlacesCounter->calculateNrOfAssignedGamePlaces());
        self::assertSame(1, $gamePlacesCounter->getNrOfGames());

        $gamePlacesCounter = $gamePlacesCounter->reset();
        self::assertSame(0, $gamePlacesCounter->calculateNrOfAssignedGamePlaces());
        self::assertSame(0, $gamePlacesCounter->getNrOfGames());

        self::assertSame($pouleOne, $gamePlacesCounter->getPoule());
    }
}
