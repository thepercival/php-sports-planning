<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\GameMode;
use SportsHelpers\SportMath;
use SportsPlanning\GameGenerator\Helper as GameGeneratorHelper;
use SportsPlanning\GameGenerator\Against as AgainstGenerator;
use SportsPlanning\GameGenerator\Together as TogetherGenerator;

class GameGenerator
{
    protected SportMath $math;

    public function __construct()
    {
        $this->math = new SportMath();
    }

    public function generateGames(Planning $planning)
    {
        $input = $planning->getInput();
        $generatorHelper = $this->getGenerator($input->getGameMode());
        foreach ($planning->getPoules() as $poule) {
            $generatorHelper->generate($poule, $input->getSportConfigs());
        }
    }

    protected function getGenerator(int $gameMode): GameGeneratorHelper
    {
        if ($gameMode === GameMode::AGAINST) {
            return new AgainstGenerator();
        }
        return new TogetherGenerator();
    }
}
