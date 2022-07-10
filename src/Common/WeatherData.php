<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

use DateTimeInterface;

class WeatherData implements WeatherDataInterface
{
    protected ?float $latitude = null;
    protected ?float $longitude = null;
    protected ?float $temperature = null;
    protected ?float $feelsLike = null;
    protected ?float $temperatureMin = null;
    protected ?float $temperatureMax = null;
    protected ?float $humidity = null;
    protected ?float $pressure = null;
    protected ?float $windSpeed = null;
    protected ?float $windDirection = null;
    protected ?float $precipitation = null;
    protected ?float $precipitationProbability = null;
    protected ?float $cloudCover = null;
    protected ?DateTimeInterface $utcDateTime = null;
    protected ?string $type = null;
    protected ?string $source = null;

    /**
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @param  float|null  $latitude
     * @return WeatherData
     */
    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * @param  float|null  $longitude
     * @return WeatherData
     */
    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    /**
     * @param  float|null  $temperature
     * @return WeatherData
     */
    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getFeelsLike(): ?float
    {
        return $this->feelsLike;
    }

    /**
     * @param  float|null  $feelsLike
     * @return WeatherData
     */
    public function setFeelsLike(?float $feelsLike): self
    {
        $this->feelsLike = $feelsLike;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTemperatureMin(): ?float
    {
        return $this->temperatureMin;
    }

    /**
     * @param  float|null  $temperatureMin
     * @return WeatherData
     */
    public function setTemperatureMin(?float $temperatureMin): self
    {
        $this->temperatureMin = $temperatureMin;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getTemperatureMax(): ?float
    {
        return $this->temperatureMax;
    }

    /**
     * @param  float|null  $temperatureMax
     * @return WeatherData
     */
    public function setTemperatureMax(?float $temperatureMax): self
    {
        $this->temperatureMax = $temperatureMax;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getHumidity(): ?float
    {
        return $this->humidity;
    }

    /**
     * @param  float|null  $humidity
     * @return WeatherData
     */
    public function setHumidity(?float $humidity): self
    {
        $this->humidity = $humidity;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPressure(): ?float
    {
        return $this->pressure;
    }

    /**
     * @param  float|null  $pressure
     * @return WeatherData
     */
    public function setPressure(?float $pressure): self
    {
        $this->pressure = $pressure;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getWindSpeed(): ?float
    {
        return $this->windSpeed;
    }

    /**
     * @param  float|null  $windSpeed
     * @return WeatherData
     */
    public function setWindSpeed(?float $windSpeed): self
    {
        $this->windSpeed = $windSpeed;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getWindDirection(): ?float
    {
        return $this->windDirection;
    }

    /**
     * @param  float|null  $windDirection
     * @return WeatherData
     */
    public function setWindDirection(?float $windDirection): self
    {
        $this->windDirection = $windDirection;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrecipitation(): ?float
    {
        return $this->precipitation;
    }

    /**
     * @param  float|null  $precipitation
     * @return WeatherData
     */
    public function setPrecipitation(?float $precipitation): self
    {
        $this->precipitation = $precipitation;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrecipitationProbability(): ?float
    {
        return $this->precipitationProbability;
    }

    /**
     * @param  float|null  $precipitationProbability
     * @return WeatherData
     */
    public function setPrecipitationProbability(?float $precipitationProbability): self
    {
        $this->precipitationProbability = $precipitationProbability;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getCloudCover(): ?float
    {
        return $this->cloudCover;
    }

    /**
     * @param  float|null  $cloudCover
     * @return WeatherData
     */
    public function setCloudCover(?float $cloudCover): self
    {
        $this->cloudCover = $cloudCover;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getUtcDateTime(): ?DateTimeInterface
    {
        return $this->utcDateTime;
    }

    /**
     * @param  DateTimeInterface|null  $utcDateTime
     * @return WeatherData
     */
    public function setUtcDateTime(?DateTimeInterface $utcDateTime): self
    {
        $this->utcDateTime = $utcDateTime;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param  string|null  $type
     * @return WeatherData
     */
    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @param  string|null  $source
     * @return WeatherData
     */
    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return mixed
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}