<?php
declare(strict_types=1);

namespace SportsPlanning\Batch;

use Psr\Log\LoggerInterface;

use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Batch as BatchBase;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;

class Output extends OutputHelper
{
    private GameOutput $gameOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->gameOutput = new GameOutput($logger);
        parent::__construct($logger);
    }

    public function output(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        string $title = null,
        int $max = null,
        int $min = null
    ): void {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($batch->getFirst(), $min, $max);
    }

    protected function outputHelper(
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule $batch,
        int|null $min = null,
        int|null $max = null
    ): void {
        if ($min !== null && $batch->getNumber() < $min) {
            $nextBatch = $batch->getNext();
            if ($nextBatch !== null) {
                $this->outputHelper($nextBatch, $max);
            }
            return;
        }
        if ($max !== null && $batch->getNumber() > $max) {
            return;
        }

        $this->outputGames($batch->getGames(), $batch);
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            $this->outputHelper($nextBatch, $max);
        }
    }

    /**
     * @param array<AgainstGame|TogetherGame> $games
     * @param BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule|null $batch
     * @return void
     */
    public function outputGames(
        array $games,
        BatchBase|SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule|null $batch = null
    ): void
    {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }
}
