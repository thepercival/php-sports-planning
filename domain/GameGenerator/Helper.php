<?php

declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\SportConfig;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;
use SportsPlanning\Poule;

interface Helper {
    /**
     * @param Poule $poule
     * @param array | SportConfig[] $sportConfigs
     * @return array | AgainstEachOtherGame[] | TogetherGame[]
     */
    public function generate(Poule $poule, array $sportConfigs): array;
}
