<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-1-19
 * Time: 15:11
 */

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use Exception;
use SportsPlanning\Place\Combination as PlaceCombination;
use SportsPlanning\Place\Combination\Number as PlaceCombinationNumber;
use SportsHelpers\Math;

class GameGenerator
{
    /**
     * @var Input
     */
    protected $input;
    /**
     * @var Math
     */
    protected $math;

    public function __construct(Input $input)
    {
        $this->input = $input;
        $this->math = new Math();
    }

    public function create(Planning $planning)
    {
        // $config = $roundNumber->getValidPlanningConfig();
        // $nrOfHeadtohead = $this->getSufficientNrOfHeadtohead($roundNumber, $config);
        for ($headtohead = 1; $headtohead <= $this->input->getNrOfHeadtohead(); $headtohead++) {
            foreach ($planning->getStructure()->getPoules() as $poule) {
                $this->createPoule($planning, $poule, $headtohead);
            }
        }
    }

    protected function createPoule(Planning $planning, Poule $poule, int $headtohead)
    {
        $gameRounds = $this->createPouleGameRounds($poule, $this->input->getTeamup());
        $reverseHomeAway = ($headtohead % 2) === 0;
        $startGameRoundNumber = (($headtohead - 1) * count($gameRounds));
        foreach ($gameRounds as $gameRound) {
            $subNumber = 1;
            foreach ($gameRound->getCombinations() as $combination) {
                $game = new Game($poule, $startGameRoundNumber + $gameRound->getNumber(), $subNumber++, $headtohead);
                $combination->createGamePlaces($game, $reverseHomeAway/*, reverseCombination*/);
            }
        }
    }

    /**
     * @param Poule $poule
     * @param bool $teamup
     * @return array|GameRound[]
     * @throws Exception
     */
    public function createPouleGameRounds(Poule $poule, bool $teamup): array
    {
        $gameRoundsSingle = $this->generateRRSchedule($poule->getPlaces()->toArray());

        $nrOfPlaces = count($poule->getPlaces());
        if ($teamup !== true || $nrOfPlaces < Input::TEAMUP_MIN || $nrOfPlaces > Input::TEAMUP_MAX) {
            return $gameRoundsSingle;
        }

        $teams = [];
        foreach ($gameRoundsSingle as $gameRound) {
            foreach ($gameRound->getCombinations() as $combination) {
                $teams[] = $combination->get();
            }
        }

        $gameRoundsTmp = [];
        // teams are all possible combinations of two pouleplaces
        foreach ($teams as $team) {
            $opponents = $this->getCombinationsWithOut($poule, $team);
            for ($nr = 1; $nr <= count($opponents); $nr++) {
                $filteredGameRounds = array_filter($gameRoundsTmp, function ($gameRoundIt) use ($nr): bool {
                    return $nr === $gameRoundIt->getNumber();
                });
                $gameRound = reset($filteredGameRounds);
                if ($gameRound === false) {
                    $gameRound = new GameRound($nr, []);
                    $gameRoundsTmp[] = $gameRound;
                }
                $combination = new PlaceCombination($team, $opponents[$nr - 1]->get());
                $gameRound->addCombination($combination);
            }
        }

        $placeCombinations = $this->flattenGameRounds($gameRoundsTmp);

        $totalNrOfCombinations = $this->getTotalNrOfCombinations($nrOfPlaces);
        if ($totalNrOfCombinations !== count($placeCombinations)) {
            throw new Exception('not correct permu', E_ERROR);
        }

        $uniquePlaceCombinations = $this->getUniquePlaceCombinations($placeCombinations);
        return $this->createGameRoundsFromUniquePlaceCombinations($nrOfPlaces, $uniquePlaceCombinations);
    }

    /**
     * @param array | PlaceCombination[] $games
     * @return array | PlaceCombination[]
     */
    protected function getUniquePlaceCombinations(array $games): array
    {
        $combinationNumbers = [];
        $uniqueGames = [];
        foreach ($games as $game) {
            $gameCombinationNumber = new PlaceCombinationNumber($game);

            if (count(array_filter($combinationNumbers, function ($combinationNumberIt) use ($gameCombinationNumber): bool {
                return $gameCombinationNumber->equals($combinationNumberIt);
            })) > 0) { // als wedstrijd al is geweest, dan wedstrijd niet opnemen
                continue;
            }
            $combinationNumbers[] = $gameCombinationNumber;
            $uniqueGames[] = $game;
        }
        return $uniqueGames;
    }

    /**
     * only be called when teamup is true
     *
     * @param array $uniqueCombinations|PlaceCombination[]
     * @return array|GameRound[]
     */
    protected function createGameRoundsFromUniquePlaceCombinations( int $nrOfPlaces, array $uniqueCombinations ): array{
        $gameRounds = [];
        $gameRound = new GameRound(1, []);
        $gameRounds[] = $gameRound;
        $nrOfGames = 0;
        $uniqueFiltered = [];
        $nrOfGamePlaces = (new \SportsPlanning\HelperTmp())->getMaxNrOfGamePlaces(
            $this->input->getSportConfigHelpers(),
            true,
            $this->input->selfRefereeEnabled()
        );

        while (count($uniqueCombinations) > 0 && count($uniqueFiltered) < count($uniqueCombinations) ) {
            $game = array_shift($uniqueCombinations);
            if ($this->isPlaceInRoundGame($gameRound->getCombinations(), $game)) {
                $uniqueFiltered[] = $game;
                continue;
            }
            $gameRound->addCombination($game);
            $nrOfGames++;

            if ( ($gameRound->getNrOfPlaces() + $nrOfGamePlaces) > $nrOfPlaces ) {
                $gameRound = new GameRound($gameRound->getNumber() + 1, []);
                $gameRounds[] = $gameRound;
            }
            $uniqueCombinations = array_merge( $uniqueCombinations, array_reverse( array_splice( $uniqueFiltered, 0 ) ) );
        }
        if (count($gameRound->getCombinations()) === 0) {
            $index = array_search($gameRound, $gameRounds, true);
            if ($index !== false) {
                unset($gameRounds[$index]);
            }
        }
        return $gameRounds;
    }

    protected function getTotalNrOfCombinations(int $nrOfPlaces): int
    {
        return $this->math->above($nrOfPlaces, 2) * $this->math->above($nrOfPlaces - 2, 2);
    }

    /**
     * @param array | Place[] $team
     * @return array | PlaceCombination[]
     */
    protected function getCombinationsWithOut(Poule $poule, array $team): array
    {
        $opponents = array_filter($poule->getPlaces()->toArray(), function ($placeIt) use ($team): bool {
            return count(array_filter($team, function ($place) use ($placeIt): bool {
                return $place === $placeIt ;
            })) === 0;
        });
        return $this->flattenGameRounds($this->generateRRSchedule($opponents));
    }

    /**
     * @param array | GameRound[] $gameRounds
     * @return PlaceCombination[] | array
     */
    protected function flattenGameRounds(array $gameRounds): array
    {
        $games = [];
        foreach ($gameRounds as $gameRound) {
            $games = array_merge($games, $gameRound->getCombinations());
        };
        return $games;
    }

    /**
     * @param array | PlaceCombination[] $gameRoundCombinations
     * @param PlaceCombination $game
     * @return bool
     */
    protected function isPlaceInRoundGame(array $gameRoundCombinations, PlaceCombination $game): bool
    {
        foreach ($gameRoundCombinations as $combination) {
            if ($combination->hasOverlap($game)) {
                return true;
            }
        }
        return false;
    }


    // kijk voor een planning van 4 hoe deze gevuld wordt!
    // STAP VOOR STAP ANALYSE!!
    /**
     * @param array | Place[] $places
     * @return array | GameRound[]
     */
    protected function generateRRSchedule(array $places): array
    {
        $nrOfPlaces = count($places);

        $nrOfHomeGames = [];
        foreach ($places as $place) {
            $nrOfHomeGames[$place->getNumber()] = 0;
        }

        // add a placeholder if the count is odd
        if ( ($nrOfPlaces % 2) > 0 ) {
            $places[] = null;
            $nrOfPlaces++;
        }

        // calculate the number of sets and matches per set
        $nrOfRoundNumbers = $nrOfPlaces - 1;
        $nrOfMatches = $nrOfPlaces / 2;
        $gameRounds = [];

        // generate each set
        for ($roundNumber = 1; $roundNumber <= $nrOfRoundNumbers; $roundNumber++) {
            $evenRoundNumber = ($roundNumber % 2) === 0;
            $combinations = [];
            // break the list in half
            $halves = array_chunk($places, $nrOfMatches);
            $firstHalf = array_shift($halves);
            // reverse the order of one half
            $secondHalf = array_reverse(array_shift($halves));
            // generate each match in the set
            for ($i = 0; $i < $nrOfMatches; $i++) {
                if ($firstHalf[$i] === null || $secondHalf[$i] === null) {
                    continue;
                }
                $homePlace = $evenRoundNumber ? $secondHalf[$i] : $firstHalf[$i];
                $awayPlace = $evenRoundNumber ? $firstHalf[$i] : $secondHalf[$i];
                if ($nrOfHomeGames[$awayPlace->getNumber()] < $nrOfHomeGames[$homePlace->getNumber()]) {
                    $tmpPlace = $homePlace;
                    $homePlace = $awayPlace;
                    $awayPlace = $tmpPlace;
                }
                $combinations[] = new PlaceCombination([$homePlace], [$awayPlace]);
                $nrOfHomeGames[$homePlace->getNumber()]++;
            }
            $gameRounds[] = new GameRound($roundNumber, $combinations);
            // remove the first player and store
            $first = array_shift($places);
            // move the second player to the end of the list
            $places[] = array_shift($places);
            // place the first item back in the first position
            array_unshift($places, $first);
        }
        return $gameRounds;
    }
}
