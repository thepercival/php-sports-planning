<?php
declare(strict_types=1);

namespace SportsPlanning\Batch;

use Psr\Log\LoggerInterface;

use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Batch as BatchBase;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;

class Output extends OutputHelper
{
    private GameOutput $gameOutput;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->gameOutput = new GameOutput($logger);
        parent::__construct($logger);
    }

    /**
     * @param BatchBase|SelfRefereeBatch $batch
     * @param string|null $title
     * @param int|null $max
     * @param int|null $min
     */
    public function output($batch, string $title = null, int $max = null, int $min = null)
    {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($batch->getFirst(), $min, $max);
    }

    /**
     * @param BatchBase|SelfRefereeBatch $batch
     * @param int|null $min
     * @param int|null $max
     */
    protected function outputHelper($batch, int $min = null, int $max = null)
    {
        if ($min !== null && $batch->getNumber() < $min) {
            if ($batch->hasNext()) {
                $this->outputHelper($batch->getNext(), $max);
            }
            return;
        }
        if ($max !== null && $batch->getNumber() > $max) {
            return;
        }

        $this->outputGames($batch->getGames(), $batch);
        if ($batch->hasNext()) {
            $this->outputHelper($batch->getNext(), $max);
        }
    }

    /**
     * @param array|AgainstGame[]|TogetherGame[] $games
     * @param BatchBase|SelfRefereeBatch|null $batch
     */
    public function outputGames(array $games, $batch = null)
    {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }
}
