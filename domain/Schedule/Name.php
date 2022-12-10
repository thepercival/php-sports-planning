<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Exception;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGame;
use SportsHelpers\Sport\Variant\Single as Single;

class   Name implements \Stringable
{
    protected string|null $name = null;

    /**
     * @param list<AgainstH2h|AgainstGpp|AllInOneGame|Single> $sportVariants
     */
    public function __construct(protected array $sportVariants)
    {
    }

    public function __toString()
    {
        if ($this->name !== null) {
            return $this->name;
        }
        $names = [];
        $nrOfAgainstH2h = 0;
        foreach ($this->sportVariants as $sportVariant) {
            if ($sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp) {
                $name = [
                    'nrOfHomePlaces' => $sportVariant->getNrOfHomePlaces(),
                    'nrOfAwayPlaces' => $sportVariant->getNrOfAwayPlaces()
                ];
                if ($sportVariant instanceof AgainstH2h) {
                    $name['nrOfH2H'] = $sportVariant->getNrOfH2H();
                    if (++$nrOfAgainstH2h > 1) {
                        throw new Exception('bij meerdere sporten mag h2h niet gebruikt worden(ScheduleName)', E_ERROR);
                    }
                } else {
                    $name['nrOfGamesPerPlace'] = $sportVariant->getNrOfGamesPerPlace();
                }
            } else {
                $name = ['nrOfGamesPerPlace' => $sportVariant->getNrOfGamesPerPlace()];
                if ($sportVariant instanceof Single) {
                    $name['nrOfGamePlaces'] = $sportVariant->getNrOfGamePlaces();
                }
            }
            $names[] = $name;
        }
        $json = json_encode($names);
        $this->name = $json === false ? '?' : $json;
        return $this->name;
    }
}
