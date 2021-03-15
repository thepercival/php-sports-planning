<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\SportMath;
use SportsPlanning\GameGenerator\Against as AgainstGenerator;
use SportsPlanning\GameGenerator\SportAndConfig;
use SportsPlanning\GameGenerator\Together as TogetherGenerator;

class GameGenerator
{
    protected SportMath $math;

    public function __construct()
    {
        $this->math = new SportMath();
    }

    public function generateGames(Planning $planning): void
    {
        $input = $planning->getInput();
        $againstGenerator = new AgainstGenerator();
        $togetherGenerator = new TogetherGenerator();
        foreach ($planning->getPoules() as $poule) {
            $againstGenerator->generate($poule, $planning->getSportAndConfigs());
            $togetherGenerator->generate($poule, $planning->getSportAndConfigs());
        }

        // hier moeten de games gegenereerd worden, op basis van creationstrategy
//        public const StaticPouleSize = 1;
//        public const StaticManual = 2;
//        public const IncrementalRandom = 3;
//        public const IncrementalRanking = 4;
    }
}
