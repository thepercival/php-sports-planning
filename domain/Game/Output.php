<?php
declare(strict_types=1);

namespace SportsPlanning\Game;

use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
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
     * @param array|AgainstGame[]|TogetherGame[] $games
     * @param string|null $prefix
     */
    public function outputGames(array $games, string $prefix = null)
    {
        foreach ($games as $game) {
            $this->output($game, null, $prefix);
        }
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @param SelfRefereeBatch|Batch|null $batch
     * @param string|null $prefix
     */
    public function output($game, $batch = null, string $prefix = null)
    {
        $useColors = $this->useColors();
        $refDescr = ($game->getRefereePlace() !== null ? $game->getRefereePlace()->getLocation() : ($game->getReferee(
        ) !== null ? $game->getReferee()->getNumber() : ''));
        $refNumber = ($useColors ? ($game->getRefereePlace() !== null ? $game->getRefereePlace()->getNumber(
        ) : ($game->getReferee() !== null ? $game->getReferee()->getNumber() : 0)) : -1);
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
            . ', sport ' . ($field !== null ? $game->getField()->getSport()->getNumber() : -1)
        );
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @param SelfRefereeBatch|Batch|null $batch
     * @return string
     */
    protected function outputPlaces($game, $batch = null): string
    {
        if ($game instanceof AgainstGame) {
            $homeGamePlaces = $this->outputPlacesHelper($game, $game->getSidePlaces(AgainstSide::HOME), $batch);
            $awayGamePlaces = $this->outputPlacesHelper($game, $game->getSidePlaces(AgainstSide::AWAY), $batch);
            return $homeGamePlaces . ' vs ' . $awayGamePlaces;
        }
        return $this->outputPlacesHelper($game, $game->getPlaces(), $batch);
    }

    /**
     * @param AgainstGame|TogetherGame $game
     * @param Collection|AgainstGamePlace[]|TogetherGamePlace[] $gamePlaces
     * @param SelfRefereeBatch|Batch|null $batch
     * @return string
     */
    protected function outputPlacesHelper($game, $gamePlaces, $batch = null): string
    {
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
