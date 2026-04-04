<?php

declare(strict_types=1);

namespace SportsPlanning\Schedules;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGame;
use SportsHelpers\Sport\Variant\Single as Single;
use SportsHelpers\Sport\VariantWithFields;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Identifiable;
use SportsPlanning\Poule;
use SportsPlanning\Schedules\ScheduleName as ScheduleName;
use SportsPlanning\Schedules\ScheduleSport;

// use SportsHelpers\ScheduleSport\VariantWithPoule;

final class Schedule extends Identifiable implements \Stringable
{
    protected string $sportsConfigName;
    protected int $succeededMargin = -1;
    protected Poule|null $poule = null;
    protected int $nrOfTimeoutSecondsTried = 0;

    /**
     * @phpstan-var ArrayCollection<int|string, ScheduleSport>|PersistentCollection<int|string, ScheduleSport>|ScheduleSport[]
     * @psalm-var ArrayCollection<int|string, ScheduleSport>
     */
    protected ArrayCollection|PersistentCollection $scheduleSports;

    /**
     * @param int $nrOfPlaces
     * @param list<Single|AgainstH2h|AgainstGpp|AllInOneGame> $sportVariants
     */
    public function __construct(protected int $nrOfPlaces, array $sportVariants)
    {
        $this->sportsConfigName = (string)new ScheduleName($sportVariants);
        $this->scheduleSports = new ArrayCollection();
    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }

    public function getSportsConfigName(): string
    {
        return $this->sportsConfigName;
    }

    /**
     * @return Collection<int|string, ScheduleSport>
     */
    public function getScheduleSports(): Collection
    {
        return $this->scheduleSports;
    }

    /**
     * @return list<Single|AgainstH2h|AgainstGpp|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        return array_map(
            function (ScheduleSport $scheduleSport): Single|AgainstH2h|AgainstGpp|AllInOneGame {
                return $scheduleSport->createVariant();
            }, array_values($this->scheduleSports->toArray())
        );
    }

//    public function createSportVariantWithPoules(): array
//    {
//        return array_values(
//                array_map( function(Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant): VariantWithPoule {
//                return new VariantWithPoule($sportVariant, $this->getNrOfPlaces());
//            } , $this->createSportVariants()->toArray() )
//        );
//    }

    /**
     * @return list<VariantWithFields>
     */
    public function createSportVariantWithFields(): array
    {
        return array_map( function(Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant): VariantWithFields {
                return new VariantWithFields($sportVariant, 1);
            } , $this->createSportVariants()
        );
    }

    public function getSucceededMargin(): int
    {
        return $this->succeededMargin;
    }

    public function setSucceededMargin(int $succeededMargin): void
    {
        $this->succeededMargin = $succeededMargin;
    }

    public function getNrOfTimeoutSecondsTried(): int
    {
        return $this->nrOfTimeoutSecondsTried;
    }

    public function setNrOfTimeoutSecondsTried(int $nrOfTimeoutSecondsTried): void
    {
        $this->nrOfTimeoutSecondsTried = $nrOfTimeoutSecondsTried;
    }

    /**
     * @return list<HomeAway>
     */
    public function createHomeAwaysForAgainstSports(): array
    {
        $homeAways = [];
        foreach( $this->getScheduleSports() as $scheduleSport ) {
            $sportVariant = $scheduleSport->createVariant();
            if( $sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp ) {
                $homeAways = array_merge( $homeAways, $scheduleSport->createHomeAways() );
            }
        }
        return $homeAways;
    }

//    public function getPoule(): Poule {
//        if( $this->poule === null ) {
//            $input = new Input( new Configuration(
//                new PlanningPouleStructure( $this->getNrOfPlaces() ),
//                array_values( array_map(
//                    function(Single|AgainstH2h|AgainstGpp|AllInOneGame $variant): VariantWithFields {
//                    return new VariantWithFields($variant, 1);
//                }, $this->createSportVariants()->toArray() ) ),
//                new PlanningRefereeInfo(),
//                false
//            ) );
//            $this->poule = $input->getPoule(1);
//        }
//        return $this->poule;
//    }

    #[\Override]
    public function __toString(): string
    {
        $XYZ = 'XYZ';
        $scheduleName = (string)new ScheduleName($this->createSportVariants());
        $json = json_encode(["nrOfPlaces" => $this->nrOfPlaces, "sportsConfigName" => $XYZ]);
        if ($json === false) {
            return '';
        }
        return str_replace($XYZ, $scheduleName, $json);
    }
}
