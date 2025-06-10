<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Poule;

use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Poule;

final class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    public function testCalculations(): void
    {
        $pouleOne = Poule::fromNrOfPlaces(1, 2);
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
