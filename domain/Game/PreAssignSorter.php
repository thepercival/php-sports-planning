<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use SportsHelpers\Sport\VariantWithPoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Planning;

class PreAssignSorter
{
    /**
     * @var array<int, array<int, int|float>>
     */
    private array $muliplierMap = [];

    /**
     * @param Planning $planning
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(Planning $planning): array
    {
        $this->initMultiplierMap($planning->getInput());

        $games = $planning->getGames();
        uasort($games, function (AgainstGame|TogetherGame $g1, AgainstGame|TogetherGame $g2): int {
            $gameRoundNumber1 = $this->getWeightedGameRoundNumber($g1);
            $gameRoundNumber2 = $this->getWeightedGameRoundNumber($g2);
            if ($gameRoundNumber1 !== $gameRoundNumber2) {
                return $gameRoundNumber1 - $gameRoundNumber2;
            }
            $nrOfPoulePlaces1 = $g1->getPoule()->getPlaces()->count();
            $nrOfPoulePlaces2 = $g2->getPoule()->getPlaces()->count();
            if ($nrOfPoulePlaces1 !== $nrOfPoulePlaces2) {
                return $nrOfPoulePlaces2 - $nrOfPoulePlaces1;
            }
            $sumPlaceNrs1 = $this->getSumPlaceNrs($g1);
            $sumPlaceNrs2 = $this->getSumPlaceNrs($g2);
            if ($sumPlaceNrs1 !== $sumPlaceNrs2) {
                return $sumPlaceNrs1 - $sumPlaceNrs2;
            }
            return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
        });
        return array_values($games);
    }

    protected function getSumPlaceNrs(AgainstGame|TogetherGame $game): int
    {
        $total = 0;
        foreach ($game->getPlaces() as $gamePlace) {
            $total += $gamePlace->getPlace()->getNumber();
        }
        return $total;
    }

//        1 1.1 vs 1.2    1 2.1 vs 2.2
//        1 1.3 vs 1.4    1 2.3 vs 2.4
//        2 1.5 vs 1.1    2 2.4 vs 2.1
//        2 1.2 vs 1.3    2 2.2 vs 2.3
//        3 1.4 vs 1.5    3 2.1 vs 2.3
//        3 1.3 vs 1.1    3 2.2 vs 2.4
//        4 1.2 vs 1.4
//        4 1.5 vs 1.3
//        5 1.1 vs 1.4
//        5 1.2 vs 1.5
//
//        1 1.1 vs 1.2    1 * 5/3 = 1.66     2.1 vs 2.2
//        1 1.3 vs 1.4    1 * 5/3 = 1.66     2.3 vs 2.4
//        2 1.5 vs 1.1    2 * 5/3 = 3.33     2.4 vs 2.1
//        2 1.2 vs 1.3    2 * 5/3 = 3.33     2.2 vs 2.3
//        3 1.4 vs 1.5    3 * 5/3 = 5        2.1 vs 2.3
//        3 1.3 vs 1.1    3 * 5/3 = 5        2.2 vs 2.4
//        4 1.2 vs 1.4
//        4 1.5 vs 1.3
//        5 1.1 vs 1.4
//        5 1.2 vs 1.5
    protected function initMultiplierMap(Input $input): int
    {
        $maxNrOfPlaces = $input->createPouleStructure()->getBiggestPoule();
        $this->muliplierMap = [];
        foreach ($input->getSports() as $sport) {
            $sportVariant = $sport->createVariant();
            $sportVariantWithBiggest = new VariantWithPoule($sportVariant, $maxNrOfPlaces);
            $maxNrOfGameGroups = $sportVariantWithBiggest->getNrOfGameGroups();
            $this->muliplierMap[$sport->getNumber()] = [];
            foreach ($input->getPoules() as $poule) {
                $sportVariantWithPoule = new VariantWithPoule($sportVariant, $maxNrOfPlaces);
                $nrOfPouleGameGroups = $sportVariantWithPoule->getNrOfGameGroups();
                // $nrOfGameRoundsPoule = $sportVariant->getNrOfGameRounds($poule->getPlaces()->count());
                $this->muliplierMap[$sport->getNumber()][$poule->getNumber(
                )] = $maxNrOfGameGroups / $nrOfPouleGameGroups;
            }
        }
        return 1;
    }

    protected function getWeightedGameRoundNumber(AgainstGame|TogetherGame $game): int
    {
        $gameRoundNumber = $this->getDefaultGameNumber($game);
        if (!isset($this->muliplierMap[$game->getSport()->getNumber()][$game->getPoule()->getNumber()])) {
            return $gameRoundNumber;
        }
        $multiplier = $this->muliplierMap[$game->getSport()->getNumber()][$game->getPoule()->getNumber()];
        return (int)($multiplier * $gameRoundNumber);
    }

    protected function getDefaultGameNumber(TogetherGame|AgainstGame $game): int
    {
        if ($game instanceof AgainstGame) {
            return $game->getGameRoundNumber();
        }
        $firstGamePlace = $game->getPlaces()->first();
        return $firstGamePlace !== false ? $firstGamePlace->getGameRoundNumber() : 0;
    }
}
