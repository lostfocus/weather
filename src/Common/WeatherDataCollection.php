<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

class WeatherDataCollection implements WeatherDataCollectionInterface
{
    private int $position;
    private ?DateTimeImmutable $maxDateTime = null;

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

        if ($this->maxDateTime === null || $weatherData->getUtcDateTime() > $this->maxDateTime) {
            $utcDateTime = $weatherData->getUtcDateTime();
            if ($utcDateTime instanceof DateTimeImmutable) {
                $this->maxDateTime = $utcDateTime;
            } elseif ($utcDateTime instanceof DateTime) {
                $this->maxDateTime = DateTimeImmutable::createFromMutable($utcDateTime);
            }
        }

        return $this;
    }

    public function getMaxDate(): ?DateTimeImmutable
    {
        return $this->maxDateTime;
    }

    public function getClosest(DateTimeInterface $dateTime): ?WeatherDataInterface
    {
        $closest = null;
        $distance = null;

        foreach ($this->items as $weatherData) {
            if ($weatherData->getUtcDateTime() === null) {
                continue;
            }
            $weatherDataDistance = abs($weatherData->getUtcDateTime()->getTimestamp() - $dateTime->getTimestamp());
            if ($distance === null || $weatherDataDistance < $distance) {
                $distance = $weatherDataDistance;
                $closest = $weatherData;
            }
        }

        return $closest;
    }
}