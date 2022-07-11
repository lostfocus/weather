<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

use DateTimeInterface;
use Lostfocus\Weather\Exceptions\WeatherException;

interface ProviderInterface
{
    public const UNIT_METRIC = 'metric';
    public const UNIT_IMPERIAL = 'imperial';

    /**
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  string  $units
     * @param  string  $lang
     * @return WeatherDataInterface
     * @throws WeatherException
     */
    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface;

    /**
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  DateTimeInterface  $dateTime
     * @param  string  $units
     * @param  string  $lang
     * @return WeatherDataInterface|null
     * @throws WeatherException
     */
    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface;

    /**
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  DateTimeInterface  $dateTime
     * @param  string  $units
     * @param  string  $lang
     * @return WeatherDataInterface|null
     * @throws WeatherException
     */
    public function getHistorical(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface;

    /**
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  string  $units
     * @param  string  $lang
     * @return WeatherDataCollectionInterface
     * @throws WeatherException
     */
    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface;
}