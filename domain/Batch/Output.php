<?php

declare(strict_types=1);

namespace SportsPlanning\Batch;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Batch as BatchBase;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Place\Output as PlaceOutput;

class Output extends OutputHelper
{
    private GameOutput $gameOutput;
    private PlaceOutput $placeOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->gameOutput = new GameOutput($logger);
        $this->placeOutput = new PlaceOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        string $title = null,
        int $max = null,
        int $min = null,
        bool $showUnassigned = false
    ): void {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($batch->getFirst(), $min, $max, $showUnassigned);
    }

    protected function outputHelper(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        int|null $min = null,
        int|null $max = null,
        bool $showUnassigned = false
    ): void {
        if ($min !== null && $batch->getNumber() < $min) {
            $nextBatch = $batch->getNext();
            if ($nextBatch !== null) {
                $this->outputHelper($nextBatch, $max, null, $showUnassigned);
            }
            return;
        }
        if ($max !== null && $batch->getNumber() > $max) {
            return;
        }

        $this->outputGames($batch->getGames(), $batch);
        if ($showUnassigned) {
            $this->outputUnassigned($batch);
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->outputHelper($nextBatch, $max, null, $showUnassigned);
        }
    }

    /**
     * @param list<AgainstGame|TogetherGame> $games
     * @param BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule|null $batch
     * @return void
     */
    public function outputGames(
        array $games,
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule|null $batch = null
    ): void {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }

    protected function outputUnassigned(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch
    ): void {
        $useColors = $this->useColors();
        $unassignedPlaces = $batch instanceof BatchBase ? $batch->getUnassignedPlaces() : $batch->getUnassignedPlaces(
            true
        );
        $placesAsArrayOfStrings = array_map(
            function (Place $place) use ($useColors, $batch): string {
                $previous = $batch->getPrevious();
                $gamesInARow = $previous?->getGamesInARow($place);
                return $this->placeOutput->getPlace(
                    $place,
                    $gamesInARow !== null ? '(' . $gamesInARow . ')' : '',
                    $useColors
                );
            },
            $unassignedPlaces
        );
        $this->logger->info(
            'unassigned places: ' . implode(' & ', $placesAsArrayOfStrings)
        );
    }
}
