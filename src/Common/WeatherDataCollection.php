<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

class WeatherDataCollection implements WeatherDataCollectionInterface
{
    private int $position;

    /**
     * @var WeatherDataInterface[]
     */
    private array $items = [];

    public function __construct()
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    public function current(): WeatherDataInterface
    {
        return $this->items[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function add(WeatherDataInterface $weatherData): self
    {
        $this->items[] = $weatherData;

        return $this;
    }
}