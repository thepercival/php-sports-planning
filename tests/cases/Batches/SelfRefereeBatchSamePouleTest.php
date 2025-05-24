<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Batches;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

class SelfRefereeBatchSamePouleTest extends TestCase
{
    use PlanningCreator;

    public function testNrOfPlacesParticipating(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $planningRefereeInfo = new PlanningRefereeInfo();
        $input = $this->createInput([5],$sportsWithNrOfFieldsAndNrOfCycles, $planningRefereeInfo);

        $planning = new Planning(
            $input, new SportRange(2,2),
            2
        );
        $poule = $input->getPoule(1);
        $place1 = $poule->getPlace(1);
        $place2 = $poule->getPlace(2);
        $sport = $input->getSport(1);
        $field = $sport->getField(1);
        $againstGame = new AgainstGame($planning, $poule, $field, 1, 1);
        new AgainstGamePlace($againstGame, $place1, AgainstSide::Home);
        new AgainstGamePlace($againstGame, $place2, AgainstSide::Away);

        $batch = new SelfRefereeBatchSamePoule(new Batch());
        $batch->add($againstGame);

        self::assertSame(3, $batch->getNrOfPlacesParticipating($poule, 1));
    }
}
