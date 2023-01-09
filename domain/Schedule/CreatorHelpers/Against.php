<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\CreatorHelpers;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Schedule\Game;
use SportsPlanning\Schedule\GamePlace;
use SportsPlanning\Schedule\Sport as SportSchedule;

abstract class Against
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    protected function createGames(SportSchedule $sportSchedule, AgainstGameRound $gameRound): void
    {
        while ($gameRound !== null) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $game = new Game($sportSchedule, $gameRound->getNumber());
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    foreach ($homeAway->get($side)->getPlaces() as $place) {
                        $gamePlace = new GamePlace($game, $place->getNumber());
                        $gamePlace->setAgainstSide($side);
                    }
                }
            }
            $gameRound = $gameRound->getNext();
        }
    }
}
