<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Batches;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\SportRange;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Planning;
use SportsPlanning\PlanningConfiguration;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\PlanningWithMeta;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class SelfRefereeBatchSamePouleTest extends TestCase
{

    public function testNrOfPlacesParticipating(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1)
        ];
        $configuration = new PlanningConfiguration(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null,
            false
        );
        $planning = Planning::fromConfiguration($configuration);

        $poule = $planning->getPoule(1);
        $sport = $planning->getSport(1);
        $field = $sport->getField(1);
        $againstGame = AgainstGame::fromPoule($poule, $field, 1, 1);
        $againstGame->addGamePlace(AgainstSide::Home, 1);
        $againstGame->addGamePlace( AgainstSide::Away, 2);

        $batch = new SelfRefereeBatchSamePoule(new Batch($planning->createPouleMap()));
        $batch->add($againstGame);

        self::assertSame(3, $batch->getNrOfPlacesParticipating($poule, 1));
    }
}
