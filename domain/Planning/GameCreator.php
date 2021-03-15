<?php
declare(strict_types=1);

namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsPlanning\GameGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Resource\Service as ResourceService;

class GameCreator
{
    protected bool $throwOnTimeout;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->throwOnTimeout = true;
    }

    public function createGames(Planning $planning): int
    {
        $gameGenerator = new GameGenerator();
        $gameGenerator->generateGames($planning);
        $games = $planning->getGames(/*Game::ORDER_BY_GAMENUMBER*/);

        $resourceService = new ResourceService($planning, $this->logger);
        if (!$this->throwOnTimeout) {
            $resourceService->disableThrowOnTimeout();
        }
        $state = $resourceService->assign($games);
        if ($state === Planning::STATE_FAILED || $state === Planning::STATE_TIMEDOUT) {
            foreach ($planning->getPoules() as $poule) {
                $poule->getAgainstGames()->clear();
                $poule->getTogetherGames()->clear();
            }
            return $state;
        }

        if (!$planning->getInput()->selfRefereeEnabled()) {
            return $state;
        }
        $firstBatch = $planning->createFirstBatch();
        $refereePlaceService = new RefereePlaceService($planning);
        if (!$this->throwOnTimeout) {
            $refereePlaceService->disableThrowOnTimeout();
        }
        return $refereePlaceService->assign($firstBatch);
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
