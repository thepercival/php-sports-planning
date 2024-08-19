<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

readonly abstract class HomeAwayAbstract implements \Stringable
{
    public function __construct(private string $index)
    {
        $this->validate();
    }

    protected function validate(): void {
        $placeNrs = $this->convertToPlaceNrs();
        foreach ($placeNrs as $placeNrOne){
            $count = 0;
            foreach ($this->convertToPlaceNrs() as $placeNrTwo){
                if ($placeNrOne === $placeNrTwo) {
                    $count++;
                }
                if( $count === 2) {
                    throw new \Exception('same placeNr cannot play in same game twice');
                }
            }
        }
    }

    /**
     * @return list<int>
     */
    abstract public function convertToPlaceNrs(): array;

    public function getIndex(): string
    {
       return $this->index;
    }

    public function __toString(): string
    {
        return $this->index;
    }
}
