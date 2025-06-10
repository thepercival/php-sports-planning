<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\Batches\Batch;
use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Output\PlaceOutput;
use SportsPlanning\Place;
use SportsPlanning\Poule;

final class GameOutput extends OutputHelper
{
    private PlaceOutput $placeOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
        $this->placeOutput = new PlaceOutput($logger);
    }

    /**
     * @param Poule $poule
     * @param list<TogetherGame|AgainstGame> $games
     * @param string|null $prefix
     * @return void
     */
    public function outputGames(Poule $poule, array $games, string $prefix = null): void
    {
        foreach ($games as $game) {
            $this->output($poule, $game, null, $prefix);
        }
    }

    public function output(
        Poule $poule,
        AgainstGame|TogetherGame                                         $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null $batch = null,
        string|null                                                      $prefix = null
    ): void {
        $useColors = $this->useColors();
        $batchNr = $game->getBatchNr();
        $cyclePartNr = 0;
        if ($game instanceof AgainstGame) {
            $cyclePartNr = $game->cyclePartNr;
        }
        $batchColor = $this->convertNumberToColor($useColors ? ($batchNr % 10) : -1);
        $fieldNr = $game->getField()->fieldNr;
        $fieldColor = $this->convertNumberToColor($useColors ? $fieldNr : -1);
        $sportNr = $game->getField()->sportNr;
        $sportColor = $this->convertNumberToColor($useColors ? $sportNr : -1);

        $this->logger->info(
            ($prefix !== null ? $prefix : '') .
            Color::getColored($batchColor, 'batch ' . $batchNr . '(' . $cyclePartNr . ')') . " " .
            // . 'substr(' . $game->getRoundNumber(), 2 ) . substr( $game->getSubNumber(), 2 ) . ") "
            'poule ' . $game->pouleNr
            . ', ' . $this->outputGamePlaces($poule, $game, $batch)
            . ' , ' . $this->getReferee($game)
            . ', ' . Color::getColored($fieldColor, 'field ' . $fieldNr)
            . ', ' . Color::getColored($sportColor, 'sport ' . $sportNr)
        );
    }

//    protected function getGameRoundNrAsString(AgainstGame|TogetherGame $game): string
//    {
//        if ($game instanceof TogetherGame) {
//            return '';
//        }
//        return '(' . $game->cyclePartNr . ')';
//    }

    protected function outputGamePlaces(
        Poule $poule,
        AgainstGame|TogetherGame                                        $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null $batch = null
    ): string {
        $useColors = $this->useColors() && $game->pouleNr === 1;
        if ($game instanceof AgainstGame) {
            $home =  $this->outputAgainstGamePlaces($poule, $game, AgainstSide::Home, $batch, $useColors);
            $away =  $this->outputAgainstGamePlaces($poule, $game, AgainstSide::Away, $batch, $useColors);
            return $home . ' vs ' . $away;
        }
        return $this->outputTogetherGamePlaces($poule, $game, $batch, $useColors);
    }

    /**
     * @param Poule $poule
     * @param TogetherGame                                                    $game
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null $batch
     * @param bool $useColors
     * @return string
     */
    protected function outputTogetherGamePlaces(
        Poule                                                            $poule,
        TogetherGame                                                     $game,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null $batch,
        bool                                                             $useColors
    ): string {
        $placesAsArrayOfStrings = array_map(
            function (int $placeNr) use ($poule, $game, $useColors, $batch): string {
                $place = $poule->getPlace($placeNr);
                $gamesInARow = $batch?->getGamesInARow($place);
                return $this->outputGamePlace($place, $gamesInARow, $useColors);
            } , $game->getPlaceNrs() );


        return implode(' & ', $placesAsArrayOfStrings);
    }

    /**
     * @param Poule                                                           $poule
     * @param AgainstGame                                                     $game
     * @param Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null $batch
     * @param bool $useColors
     * @return string
     */
    protected function outputAgainstGamePlaces(
        Poule                                                            $poule,
        AgainstGame                                                      $game,
        AgainstSide                                                      $side,
        Batch|SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoules|null $batch,
        bool                                                             $useColors
    ): string {
        $placesAsArrayOfStrings = array_map(
            function (AgainstGamePlace $againstGamePlace) use ($poule, $game, $useColors, $batch): string {
                $place = $poule->getPlace($againstGamePlace->placeNr);
                $gamesInARow = $batch?->getGamesInARow($place);
                return $this->outputGamePlace($place, $gamesInARow, $useColors);
            } , $game->getSideGamePlaces($side) );

        return implode(' & ', $placesAsArrayOfStrings);
    }

    protected function outputGamePlace(
        Place                               $place,
        int|null                            $gamesInARow,
        bool                                $useColors
    ): string {
        return $this->placeOutput->getPlace(
            $place,
            $gamesInARow !== null ? '(' . $gamesInARow . ')' : '',
            $useColors
        );
    }

    protected function getReferee(AgainstGame|TogetherGame $game): string
    {
        $useColors = $this->useColors() && $game->pouleNr === 1;
        $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
        if ($refereePlaceUniqueIndex !== null) {
            // $refColor = $this->convertNumberToColor($useColors ? $refereePlace->placeNr : -1);
//            return Color::getColored($refColor, 'ref ' . $refereePlaceUniqueIndex);
            return 'ref ' . $refereePlaceUniqueIndex;
        }
        $refereeNr = $game->getRefereeNr();
        if ($refereeNr === null) {
            return 'ref ?';
        }
        $refColor = $this->convertNumberToColor($useColors ? $refereeNr : -1);
        return Color::getColored($refColor, 'ref ' . $refereeNr);
    }
}
