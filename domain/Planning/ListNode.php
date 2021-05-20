<?php
declare(strict_types=1);

namespace SportsPlanning\Planning;

/**
 * @template T
 */
class ListNode
{
    protected int $number;
    /**
     * @var T|null
     */
    protected mixed $next = null;

    /**
     * @param T|null $previous
     */
    public function __construct(protected mixed $previous)
    {
        $this->number = $previous instanceof ListNode ? $previous->getNumber() + 1 : 1;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    /**
     * @return T|null
     */
    public function getPrevious(): mixed
    {
        return $this->previous;
    }

    /**
     * @phpstan-return T
     * @psalm-return static<T>
     */
    public function getFirst(): mixed
    {
        $previous = $this->getPrevious();
        return $previous instanceof self ? $previous->getFirst() : $this;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    /**
     * @return T|null
     */
    public function getNext(): mixed
    {
        return $this->next;
    }

    /**
     * @phpstan-return T
     * @psalm-return static<T>
     */
    public function getLeaf(): mixed
    {
        $next = $this->getNext();
        return $next instanceof self ? $next->getLeaf() : $this;
    }
}
