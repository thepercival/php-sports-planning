<?php

declare(strict_types=1);

namespace SportsPlanning\Resource\Service;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\PouleCounter;
use SportsPlanning\Sport;

class SportInfo
{
    protected int $nrOfGames = 0;
    /**
     * @var array<int, PouleCounter> $pouleGameCounters
     */
    protected array $pouleGameCounters = [];
    protected Single|AgainstH2h|AgainstGpp|AllInOneGame|null $variant = null;

    /**
     * @param Sport $sport
     */
    public function __construct(protected Sport $sport)
    {
    }

    public function addGame(AgainstGame|TogetherGame $game): void
    {
        $this->nrOfGames++;
        $pouleNr = $game->getPoule()->getNumber();
        if (!array_key_exists($pouleNr, $this->pouleGameCounters)) {
            $this->pouleGameCounters[$pouleNr] = new PouleCounter($game->getPoule());
        }
        $this->pouleGameCounters[$pouleNr]->addGame($game);
    }

    /**
     * @return array<int, PouleCounter>
     */
    public function getPouleGameCounters(): array
    {
        return $this->pouleGameCounters;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }

    public function getVariant(): Single|AgainstH2h|AgainstGpp|AllInOneGame
    {
        if ($this->variant === null) {
            $this->variant = $this->getSport()->createVariant();
        }
        return $this->variant;
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }
}
