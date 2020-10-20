<?php


namespace SportsPlanning\Planning;

use Psr\Log\LoggerInterface;
use SportsPlanning\Game;
use SportsPlanning\GameGenerator;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Planning\Seeker\NextBatchGamesPlanningCalculator;
use SportsPlanning\Planning\Seeker\NextGamesInARowPlanningCalculator;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Resource\Service as ResourceService;

class GameCreator
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    protected bool $throwOnTimeout;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->throwOnTimeout = true;
    }

    public function createGames(Planning $planning): int
    {
        $gameGenerator = new GameGenerator($planning->getInput());
        $gameGenerator->create($planning);
        $games = $planning->getGames(Game::ORDER_BY_GAMENUMBER);

        $resourceService = new ResourceService($planning, $this->logger);
        if( !$this->throwOnTimeout ) {
            $resourceService->disableThrowOnTimeout();
        }
        $state = $resourceService->assign($games);
        if ($state === Planning::STATE_FAILED || $state === Planning::STATE_TIMEDOUT) {
            foreach ($planning->getPoules() as $poule) {
                $poule->getGames()->clear();
            }
            return $state;
        }

        if (!$planning->getInput()->selfRefereeEnabled()) {
            return $state;
        }
        $firstBatch = $planning->createFirstBatch();
        $refereePlaceService = new RefereePlaceService($planning);
        if( !$this->throwOnTimeout ) {
            $refereePlaceService->disableThrowOnTimeout();
        }
        return $refereePlaceService->assign($firstBatch);
    }

    public function disableThrowOnTimeout() {
        $this->throwOnTimeout = false;
    }
}
