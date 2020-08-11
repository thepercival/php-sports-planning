<?php

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;

use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Batch as BatchBase;
use SportsPlanning\Output\Game as GameOutput;
use SportsPlanning\Game as GameBase;

class Batch extends OutputHelper
{
    /**
     * @var Game
     */
    private $gameOutput;

    public function __construct( LoggerInterface $logger = null )
    {
        $this->gameOutput = new GameOutput( $logger );
        parent::__construct( $logger );
    }

    public function output(BatchBase $batch, string $title = null, int $max = null)
    {
        if ($title === null) {
            $title = '';
        }
//        if( $batch->getNumber() > 2 ) {
//            return;
//        }
        $this->logger->info('------batch ' . $batch->getNumber() . ' ' . $title . ' -------------');
        $this->outputHelper($batch->getFirst(), $max);
    }

    protected function outputHelper(BatchBase $batch, int $max = null)
    {
        if ($max !== null && $batch->getNumber() > $max) {
            return;
        }

        $this->outputGames($batch->getGames(), $batch);
        if ($batch->hasNext()) {
            $this->outputHelper($batch->getNext(), $max);
        }
    }

    /**
     * @param array|GameBase[] $games
     * @param BatchBase|null $batch
     */
    public function outputGames(array $games, BatchBase $batch = null)
    {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }
}
