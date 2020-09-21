<?php

namespace SportsPlanning\Batch;

use Psr\Log\LoggerInterface;

use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Batch as BatchBase;
use SportsPlanning\Game as GameBase;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;

class Output extends OutputHelper
{
    /**
     * @var GameOutput
     */
    private $gameOutput;

    public function __construct( LoggerInterface $logger = null )
    {
        $this->gameOutput = new GameOutput( $logger );
        parent::__construct( $logger );
    }

    /**
     * @param BatchBase|SelfRefereeBatch $batch
     * @param string|null $title
     * @param int|null $max
     */
    public function output($batch, string $title = null, int $max = null)
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

    /**
     * @param BatchBase|SelfRefereeBatch $batch
     * @param int|null $max
     */
    protected function outputHelper($batch, int $max = null)
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
     * @param BatchBase|SelfRefereeBatch|null $batch
     */
    public function outputGames(array $games, $batch = null)
    {
        foreach ($games as $game) {
            $this->gameOutput->output($game, $batch);
        }
    }
}
