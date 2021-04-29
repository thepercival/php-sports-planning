<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\Against\Side as AgainstSide;

class HomeAwayList
{
    private bool $shift = true;

    /**
     * @param list<AgainstHomeAway> $homeAways
     */
    public function __construct(
        private array $homeAways
    ) {
    }

    public function pop(): AgainstHomeAway|false
    {
        $homeAway = $this->shift ? array_shift($this->homeAways) : array_pop($this->homeAways);
        $this->shift = !$this->shift;
        return $homeAway;
    }

    /**
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(): array
    {
        $homeAways = [];
        $homeAway = $this->shift ? array_shift($this->homeAways) : array_pop($this->homeAways);
        $this->shift = !$this->shift;
        while( $homeAway !== null ) {
            array_push($homeAways, $homeAway);
            $homeAway = $this->shift ? array_shift($this->homeAways) : array_pop($this->homeAways);
            $this->shift = !$this->shift;
        }

        return $homeAways;
    }

    /**
     * @return list<AgainstHomeAway>
     */
    public function getLinear(): array
    {
        return $this->homeAways;
    }
}
