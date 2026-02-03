<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Output\Color;
use SportsHelpers\Output\OutputAbstract;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Output\PlaceOutput as PlaceOutput;

final class GameOutput extends OutputAbstract
{
    private PlaceOutput $placeOutput;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->placeOutput = new PlaceOutput($logger);
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @param string|null $prefix
     * @return void
     */
    public function outputGames(array $games, string|null $prefix = null): void
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
        $batchNr = $game->getBatchNr();
        $gameRoundNumber = 0;
        if ($game instanceof AgainstGame) {
            $gameRoundNumber = $game->getGameRoundNumber();
        }
        $batchColor = $this->convertNumberToColor($batchNr % 10);
        $fieldNr = $game->getField()->getNumber();
        $fieldColor = $this->convertNumberToColor($fieldNr);
        $sportNr = $game->getSport()->getNumber();
        $sportColor = $this->convertNumberToColor($sportNr);

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            $this->getColoredString($batchColor, 'batch ' . $batchNr . '(' . $gameRoundNumber . ')') . " " .
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->getPoule()->getNumber()
            . ', ' . $this->getPlaces($game, $batch)
            . ' , ' . $this->getReferee($game)
            . ', ' . $this->getColoredString($fieldColor, 'field ' . $fieldNr)
            . ', ' . $this->getColoredString($sportColor, 'sport ' . $sportNr)
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
        AgainstGame|TogetherGame $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch = null
    ): string {
        if ($game instanceof AgainstGame) {
            $homeGamePlaces = $this->getPlacesHelper($game->getSidePlaces(AgainstSide::Home), $batch);
            $awayGamePlaces = $this->getPlacesHelper($game->getSidePlaces(AgainstSide::Away), $batch);
            return $homeGamePlaces . ' vs ' . $awayGamePlaces;
        }
        return $this->getPlacesHelper($game->getPlaces(), $batch);
    }

    /**
     * @param Collection<int|string,AgainstGamePlace>|Collection<int|string,TogetherGamePlace> $gamePlaces
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch
     * @param bool $useColors
     * @return string
     */
    protected function getPlacesHelper(
        Collection $gamePlaces,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule|null $batch
    ): string {
        $placesAsArrayOfStrings = array_map(
            function (AgainstGamePlace|TogetherGamePlace $gamePlace) use ($batch): string {
                $gamesInARow = $batch?->getGamesInARow($gamePlace->getPlace());
                return $this->getPlace($gamePlace, $gamesInARow);
            } , $gamePlaces->toArray() );
        return implode(' & ', $placesAsArrayOfStrings);
    }

    protected function getPlace(
        AgainstGamePlace|TogetherGamePlace $gamePlace,
        int|null $gamesInARow
    ): string {
        return $this->placeOutput->getPlace(
            $gamePlace->getPlace(),
            $gamesInARow !== null ? '(' . $gamesInARow . ')' : ''
        );
    }

    protected function getReferee(AgainstGame|TogetherGame $game): string
    {
        $refereePlace = $game->getRefereePlace();
        if ($refereePlace !== null) {
            $refPlaceColor = $this->convertNumberToColor($refereePlace->getPlaceNr());
            return $this->getColoredString($refPlaceColor, 'ref ' . ((string)$refereePlace));
        }
        $referee = $game->getReferee();
        if ($referee === null) {
            return 'ref ?';
        }
        $refColor = $this->convertNumberToColor($referee->getNumber());
        return $this->getColoredString($refColor, 'ref ' . $referee->getNumber());
    }
}
