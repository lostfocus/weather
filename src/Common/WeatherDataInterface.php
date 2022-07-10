<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

use DateTimeInterface;
use JsonSerializable;

interface WeatherDataInterface extends JsonSerializable
{
    public const CURRENT = 'current';
    public const HISTORICAL = 'historical';
    public const FORECAST = 'forecast';

    public function getLatitude(): ?float;
    public function setLatitude(?float $latitude): self;
    public function getLongitude(): ?float;
    public function setLongitude(?float $longitude): self;

    public function getTemperature(): ?float;
    public function setTemperature(?float $temperature): self;
    public function getFeelsLike(): ?float;
    public function setFeelsLike(?float $feelsLike): self;
    public function getTemperatureMin(): ?float;
    public function setTemperatureMin(?float $temperatureMin): self;
    public function getTemperatureMax(): ?float;
    public function setTemperatureMax(?float $temperatureMax): self;

    public function getHumidity(): ?float;
    public function setHumidity(?float $humidity): self;
    public function getPressure(): ?float;
    public function setPressure(?float $pressure): self;

    public function getWindSpeed(): ?float;
    public function setWindSpeed(?float $windSpeed): self;
    public function getWindDirection(): ?float;
    public function setWindDirection(?float $windDirection): self;

    public function getPrecipitation(): ?float;
    public function setPrecipitation(?float $precipitation): self;

    public function getUtcDateTime(): ?DateTimeInterface;
    public function setUtcDateTime(?DateTimeInterface $utcDateTime): self;
    public function getType(): ?string;
    public function setType(?string $type): self;
    public function getSource(): ?string;
    public function setSource(?string $source): self;
}