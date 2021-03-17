<?php
declare(strict_types=1);

namespace SportsPlanning\Game;

use Psr\Log\LoggerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;

class Output extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @param string|null $prefix
     * @return void
     */
    public function outputGames(array $games, string $prefix = null): void
    {
        foreach ($games as $game) {
            $this->output($game, null, $prefix);
        }
    }

    public function output(
        AgainstGame|TogetherGame $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null,
        string|null $prefix = null
    ): void {
        $useColors = $this->useColors();
        $refereePlace = $game->getRefereePlace();
        $referee = $game->getReferee();
        $refDescr = ($refereePlace !== null ? $refereePlace->getLocation() : ($referee !== null ? $referee->getNumber() : ''));
        $refNumber = ($useColors ? ($refereePlace !== null ? $refereePlace->getNumber() : ($referee !== null ? $referee->getNumber() : 0)) : -1);
        $batchColor = $useColors ? ($game->getBatchNr() % 10) : -1;
        $field = $game->getField();
        $fieldNr = $field !== null ? $field->getNumber() : -1;
        $fieldColor = $useColors ? $fieldNr : -1;
        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $this->outputColor($batchColor, 'batch ' . $game->getBatchNr()) . " " .
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->outputPlaces($game, $batch)
            . ' , ' . $this->outputColor($refNumber, 'ref ' . $refDescr)
            . ', ' . $this->outputColor($fieldColor, 'field ' . $fieldNr)
            . ', sport ' . ($field !== null ? $game->getSport()->getNumber() : -1)
        );
    }

    protected function outputPlaces(
        AgainstGame|TogetherGame $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null
    ): string {
        if ($game instanceof AgainstGame) {
            $homeGamePlaces = $this->outputAgainstPlaces($game, $game->getSidePlaces(AgainstSide::HOME), $batch);
            $awayGamePlaces = $this->outputAgainstPlaces($game, $game->getSidePlaces(AgainstSide::AWAY), $batch);
            return $homeGamePlaces . ' vs ' . $awayGamePlaces;
        }
        return $this->outputTogetherPlaces($game, $game->getPlaces(), $batch);
    }

    /**
     * @param AgainstGame $game
     * @param ArrayCollection<int|string,AgainstGamePlace> $gamePlaces
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch
     * @return string
     */
    protected function outputAgainstPlaces(
        AgainstGame $game,
        ArrayCollection $gamePlaces,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null
    ): string {
        //        $homeGamePlaces = $this->outputPlaces($game, $game->getPlaces(AgainstGame::HOME), $batch);
//        $awayGamePlaces = $this->outputPlaces($game, $game->getPlaces(AgainstGame::AWAY), $batch);
//        $homeGamePlaces . ' vs ' . $awayGamePlaces

        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        $placesAsArrayOfStrings = $gamePlaces->map(
            function ($gamePlace) use ($useColors, $batch): string {
                $colorNumber = $useColors ? $gamePlace->getPlace()->getNumber() : -1;
                $gamesInARow = $batch !== null ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
                return $this->outputColor($colorNumber, $gamePlace->getPlace()->getLocation() . $gamesInARow);
            }
        )->toArray();
        return implode(' & ', $placesAsArrayOfStrings);
    }

    /**
     * @param TogetherGame $game
     * @param ArrayCollection<int|string,TogetherGamePlace> $gamePlaces
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch
     * @return string
     */
    protected function outputTogetherPlaces(
        AgainstGame|TogetherGame $game,
        ArrayCollection $gamePlaces,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null
    ): string {
        //        $homeGamePlaces = $this->outputPlaces($game, $game->getPlaces(AgainstGame::HOME), $batch);
//        $awayGamePlaces = $this->outputPlaces($game, $game->getPlaces(AgainstGame::AWAY), $batch);
//        $homeGamePlaces . ' vs ' . $awayGamePlaces

        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        $placesAsArrayOfStrings = $gamePlaces->map(
            function ($gamePlace) use ($useColors, $batch): string {
                $colorNumber = $useColors ? $gamePlace->getPlace()->getNumber() : -1;
                $gamesInARow = $batch !== null ? ('(' . $batch->getGamesInARow($gamePlace->getPlace()) . ')') : '';
                return $this->outputColor($colorNumber, $gamePlace->getPlace()->getLocation() . $gamesInARow);
            }
        )->toArray();
        return implode(' & ', $placesAsArrayOfStrings);
    }
}
