<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 21:32
 */

namespace Voetbal\TestHelper;

use SportsPlanning;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Service as PlanningService;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Input\Service as PlanningInputService;
use Voetbal\Round\Number as RoundNumber;

trait PlanningCreator {
    protected function createPlanning( RoundNumber $roundNumber, array $options ): Planning
    {
        $planningInputService = new PlanningInputService();
        $nrOfReferees = $roundNumber->getCompetition()->getReferees()->count();
        $planningInput = $planningInputService->get($roundNumber, $nrOfReferees);
        $planningService = new PlanningService();
        $planning = $planningService->createNextMinIsMaxPlanning($planningInput);
        if (Planning::STATE_SUCCESS !== $planningService->createGames($planning)) {
            throw new \Exception("planning could not be created", E_ERROR);
        }
        if ($roundNumber->getValidPlanningConfig()->selfRefereeEnabled()) {
            $refereePlaceService = new RefereePlaceService($planning);
            $refereePlaceService->assign($planning->createFirstBatch());
        }
        return $planning;
    }
}

