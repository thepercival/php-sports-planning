<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame as AgainstGame;
use SportsPlanning\Game\AgainstGamePlace as AgainstGamePlace;
use SportsPlanning\Game\TogetherGame as TogetherGame;
use SportsPlanning\Game\TogetherGamePlace as TogetherGamePlace;
use SportsPlanning\Output\PlaceOutput as PlaceOutput;

class GameOutput extends OutputHelper
{
    private PlaceOutput $placeOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->placeOutput = new PlaceOutput($logger);
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
        AgainstGame|TogetherGame                        $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null,
        string|null                                                     $prefix = null
    ): void {
        $useColors = $this->useColors();
        $batchNr = $game->getBatchNr();
        $gameRoundNumber = 0;
        if ($game instanceof AgainstGame) {
            $gameRoundNumber = $game->getGameRoundNumber();
        }
        $batchColor = $this->convertNumberToColor($useColors ? ($batchNr % 10) : -1);
        $fieldNr = $game->getField()->getNumber();
        $fieldColor = $this->convertNumberToColor($useColors ? $fieldNr : -1);
        $sportNr = $game->getSport()->getNumber();
        $sportColor = $this->convertNumberToColor($useColors ? $sportNr : -1);

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            Color::getColored($batchColor, 'batch ' . $batchNr . '(' . $gameRoundNumber . ')') . " " .
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->getPlaces($game, $batch)
            . ' , ' . $this->getReferee($game)
            . ', ' . Color::getColored($fieldColor, 'field ' . $fieldNr)
            . ', ' . Color::getColored($sportColor, 'sport ' . $sportNr)
        );
    }

    protected function getGameRoundNrAsString(AgainstGame|TogetherGame $game): string
    {
        if ($game instanceof TogetherGame) {
            return '';
        }
        return '(' . $game->getGameRoundNumber() . ')';
    }

    protected function getPlaces(
        AgainstGame|TogetherGame                        $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null
    ): string {
        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        if ($game instanceof AgainstGame) {
            $homeGamePlaces = $this->getPlacesHelper($game->getSidePlaces(AgainstSide::Home), $batch, $useColors);
            $awayGamePlaces = $this->getPlacesHelper($game->getSidePlaces(AgainstSide::Away), $batch, $useColors);
            return $homeGamePlaces . ' vs ' . $awayGamePlaces;
        }
        return $this->getPlacesHelper($game->getPlaces(), $batch, $useColors);
    }

    /**
     * @param Collection<int|string,AgainstGamePlace>|Collection<int|string,TogetherGamePlace> $gamePlaces
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch
     * @param bool $useColors
     * @return string
     */
    protected function getPlacesHelper(
        Collection $gamePlaces,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch,
        bool $useColors
    ): string {
        $placesAsArrayOfStrings = array_map(
            function (AgainstGamePlace|TogetherGamePlace $gamePlace) use ($useColors, $batch): string {
                $gamesInARow = $batch?->getGamesInARow($gamePlace->getPlace());
                return $this->getPlace($gamePlace, $gamesInARow, $useColors);
            } , $gamePlaces->toArray() );
        return implode(' & ', $placesAsArrayOfStrings);
    }

    protected function getPlace(
        AgainstGamePlace|TogetherGamePlace $gamePlace,
        int|null                                           $gamesInARow,
        bool                                               $useColors
    ): string {
        return $this->placeOutput->getPlace(
            $gamePlace->getPlace(),
            $gamesInARow !== null ? '(' . $gamesInARow . ')' : '',
            true
        );
    }

    protected function getReferee(AgainstGame|TogetherGame $game): string
    {
        $useColors = $this->useColors() && $game->getPoule()->getNumber() === 1;
        $refereePlace = $game->getRefereePlace();
        if ($refereePlace !== null) {
            $refColor = $this->convertNumberToColor($useColors ? $refereePlace->getPlaceNr() : -1);
            return Color::getColored($refColor, 'ref ' . $refereePlace);
        }
        $referee = $game->getReferee();
        if ($referee === null) {
            return 'ref ?';
        }
        $refColor = $this->convertNumberToColor($useColors ? $referee->getNumber() : -1);
        return Color::getColored($refColor, 'ref ' . $referee->getNumber());
    }
}
