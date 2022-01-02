<?php

namespace SportsPlanning\Resource\Service;

use Psr\Log\LoggerInterface;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class Helper
{
    private const ThresHoldPercentage = 50;
    protected bool $balancedStructure;
    protected int $totalNrOfGames;
    protected Input $input;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->input = $planning->getInput();
        $this->balancedStructure = $this->input->createPouleStructure()->isBalanced();

        $sportVariants = array_values($this->input->createSportVariants()->toArray());
        $this->totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
    }

    /**
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $previousBatch
     * @param array<TogetherGame|AgainstGame> $gamesForBatchTmp
     */
    public function sortGamesByNotInPreviousBatch(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $previousBatch,
        array &$gamesForBatchTmp
    ): void {
        uasort(
            $gamesForBatchTmp,
            function (TogetherGame|AgainstGame $gameA, TogetherGame|AgainstGame $gameB) use ($previousBatch): int {
                $amountA = count(
                    $gameA->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                $amountB = count(
                    $gameB->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                return $amountB - $amountA;
            }
        );
    }

    /**
     * @param int $batchNumber
     * @param list<TogetherGame|AgainstGame> $games
     * @return bool
     */
    public function gamesCanBeAssignedToMinNrOfBatchGames(int $batchNumber, array $games): bool
    {
        if ($this->input->hasMultipleSports()) {
            return true;
        }
        $singleSport = $this->input->getSport(1);
        if (!$this->balancedStructure) {
            return $this->biggestPouleGamesStillCanBeAssigned($games);
        }
        if ((count($games) / $this->totalNrOfGames) >= (self::ThresHoldPercentage / 100)) {
            return true;
        }

        $gamesPerPouleMap = [];
        foreach ($this->input->getPoules() as $poule) {
            $gamesPerPouleMap[$poule->getNumber()] = 0;
        }
        foreach ($games as $game) {
            $gamesPerPouleMap[$game->getPoule()->getNumber()]++;
        }


        $maxNrOfBatchesToGo = $this->planning->getMaxNrOfBatches() - $batchNumber; // $batch->getNumber();
        foreach ($gamesPerPouleMap as $pouleNr => $nrOfPouleGamesToAssign) {
            $poule = $this->input->getPoule($pouleNr);
            $maxNrOfGamesSim = $this->getMaxNrOfSimultanousPouleGames($singleSport, $poule->getPlaces()->count());
            if ($nrOfPouleGamesToAssign > ($maxNrOfBatchesToGo * $maxNrOfGamesSim)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @return bool
     */
    public function biggestPouleGamesStillCanBeAssigned(array $games): bool
    {
        $biggestPoule = $this->input->getFirstPoule();
        $nrOfPlaces = $biggestPoule->getPlaces()->count();
        $gamesForPoule = $this->getGamesForPoule($biggestPoule, $games);

        $game = reset($games);
        if ($game === false) {
            return true;
        }

        $maxNrOfGamesSim = $this->getMaxNrOfSimultanousPouleGames($game->getSport(), $nrOfPlaces);

        $maxNrOfPouleGamesPerBatch = $this->input->getMaxNrOfBatchGames();
        if ($maxNrOfGamesSim < $maxNrOfPouleGamesPerBatch) {
            $maxNrOfPouleGamesPerBatch = $maxNrOfGamesSim;
        }
        $nrOfBatchesNeeded = (int)ceil(count($gamesForPoule) / $maxNrOfPouleGamesPerBatch);

        $nrOfOtherPoulesGames = count($games) - count($gamesForPoule);
        $minNrOfOtherPoulesGamesPerBatch = $this->planning->getMinNrOfBatchGames() - $maxNrOfPouleGamesPerBatch;
        $nrOfOtherPoulesGamesNeeded = $nrOfBatchesNeeded * $minNrOfOtherPoulesGamesPerBatch;
        return $nrOfOtherPoulesGames >= $nrOfOtherPoulesGamesNeeded;
    }

    /**
     * @param Poule $poule
     * @param list<TogetherGame|AgainstGame> $games
     * @return list<TogetherGame|AgainstGame>
     */
    public function getGamesForPoule(Poule $poule, array $games): array
    {
        return array_values(
            array_filter($games, function ($game) use ($poule): bool {
                return $game->getPoule() === $poule;
            })
        );
    }

    protected function getMaxNrOfSimultanousPouleGames(Sport $singleSport, int $nrOfPoulePlaces): int
    {
        // aantal wedstrijden per batch
        $selfRefereeSamePoule = $this->input->getSelfReferee() === SelfReferee::SamePoule;
        $sportVariant = $singleSport->createVariant();
        $nrOfGamePlaces = $this->getNrOfGamePlaces($sportVariant, $nrOfPoulePlaces, $selfRefereeSamePoule);

        return (int)floor($nrOfPoulePlaces / $nrOfGamePlaces);
    }

    public function getNrOfGamePlaces(
        SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant $sportVariant,
        int $nrOfPlaces,
        bool $selfRefereeSamePoule
    ): int {
        if ($sportVariant instanceof AgainstSportVariant) {
            return $sportVariant->getNrOfGamePlaces() + ($selfRefereeSamePoule ? 1 : 0);
        } elseif ($sportVariant instanceof SingleSportVariant) {
            return $sportVariant->getNrOfGamePlaces() + ($selfRefereeSamePoule ? 1 : 0);
        }
        return $nrOfPlaces;
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @return int
     */
    public function calculateMaxNrOfBatchGames(array $games): int
    {
        $maxNrOfBatchGames = $this->planning->getMaxNrOfBatchGames();
        if (!$this->input->hasMultipleSports()) {
            return $maxNrOfBatchGames;
        }

        // tel de velden vande sporten van de games op en kijk als dat minder
        $maxNrOfBatchGamesByFields = 0;
        foreach ($this->getAssignableSportMap($games) as $sport) {
            $maxNrOfBatchGamesByFields += $sport->getFields()->count();
        }

        if ($maxNrOfBatchGamesByFields < $maxNrOfBatchGames) {
            return $maxNrOfBatchGamesByFields;
        }
        return $maxNrOfBatchGames;
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @return array<int|string, Sport>
     */
    protected function getAssignableSportMap(array $games): array
    {
        $assignableSportMap = [];
        foreach ($games as $game) {
            $sportNr = $game->getSport()->getNumber();
            if (!isset($assignableSportMap[$sportNr])) {
                $assignableSportMap[$sportNr] = $game->getSport();
            }
        }
        return $assignableSportMap;
    }
}
