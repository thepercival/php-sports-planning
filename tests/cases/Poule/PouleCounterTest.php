<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Poule;

use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\TestHelper\PlanningCreator;

class PouleCounterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator;

    public function testCalculations(): void
    {
        $input = $this->createInput([3]);

        $pouleOne = $input->getPoule(1);
        $gamePlacesCounter = new GamePlacesCounterForPoule($pouleOne);

        $nrOfPlacesAssigned = 3;
        $gamePlacesCounter = $gamePlacesCounter->add($nrOfPlacesAssigned);

        self::assertSame($nrOfPlacesAssigned, $gamePlacesCounter->getNrOfPlacesAssigned());
        self::assertSame(1, $gamePlacesCounter->getNrOfGames());

        $gamePlacesCounter = $gamePlacesCounter->reset();
        self::assertSame(0, $gamePlacesCounter->getNrOfPlacesAssigned());
        self::assertSame(0, $gamePlacesCounter->getNrOfGames());

        self::assertSame($pouleOne, $gamePlacesCounter->getPoule());
    }
}
