<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\GameRounds;

use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;
use SportsPlanning\Planning\ListNode;

/**
 * @template-extends ListNode<AgainstGameRound>
 */
class AgainstGameRound extends ListNode
{
    protected AmountNrCounterMap $placeNrCounterMap;

    /**
     * @var list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    protected array $homeAways = [];

    public function __construct(AgainstGameRound|null $previous = null)
    {
        $this->placeNrCounterMap = new AmountNrCounterMap();
        parent::__construct($previous);
    }

    public function isParticipating(int $placeNr): bool
    {
        return $this->placeNrCounterMap->count($placeNr) > 0;
    }

    public function createNext(): AgainstGameRound
    {
        $this->next = new AgainstGameRound($this);
        return $this->next;
    }

    public function add(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->placeNrCounterMap->addHomeAway($homeAway);
    }

    public function remove(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): void
    {
        $this->placeNrCounterMap->removeHomeAway($homeAway);
    }

    public function swapSidesOfHomeAway(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $reversedHomeAway): bool
    {
        foreach( $this->homeAways as $needle => $homeAwayIt) {
            $revOneVsOne = $reversedHomeAway instanceof OneVsOneHomeAway;
            $revOneVsTwo = $reversedHomeAway instanceof OneVsTwoHomeAway;
            $revTwoVsTwo = $reversedHomeAway instanceof TwoVsTwoHomeAway;

            $haOneVsOne = $homeAwayIt instanceof OneVsOneHomeAway;
            $haOneVsTwo = $homeAwayIt instanceof OneVsTwoHomeAway;
            $haTwoVsTwo = $homeAwayIt instanceof TwoVsTwoHomeAway;

            if( $homeAwayIt->equals($reversedHomeAway) ) {
                array_splice($this->homeAways, $needle, 1, [$reversedHomeAway]);
                return true;
            }
        }
        return false;
    }

    /**
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function getHomeAways(): array
    {
        return $this->homeAways;
    }

    public function isSomeHomeAwayPlaceNrParticipating(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool
    {
        foreach ($homeAway->convertToPlaceNrs() as $placeNr) {
            if ($this->isParticipating($placeNr)) {
                return true;
            }
        }
        return false;
    }

    public function getNrOfHomeAwaysRecursive(): int {
        $previous = $this->getPrevious();
        if( $previous !== null ) {
            return count($this->getHomeAways()) + $previous->getNrOfHomeAwaysRecursive();
        }
        return count($this->getHomeAways());
    }

    /**
     * @return list<OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway>
     */
    public function getAllHomeAways(): array
    {
        $homeAways = [];
        $gameRound = $this->getFirst();
        while ($gameRound) {
            foreach ($gameRound->getHomeAways() as $homeAway) {
                $homeAways[] = $homeAway;
            }
            $gameRound = $gameRound->getNext();
        }
        return $homeAways;
    }
}
