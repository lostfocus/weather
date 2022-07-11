<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\Meta;

use DateTimeInterface;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\ProviderInterface;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\ForecastNotPossibleException;
use Lostfocus\Weather\Exceptions\HistoricalDataNotAvailableException;
use Lostfocus\Weather\Exceptions\WeatherException;

class ChainProvider extends AbstractProvider
{
    private ProviderInterface $primaryProvider;
    private ProviderInterface $secondaryProvider;

    /**
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(ProviderInterface $primaryProvider, ProviderInterface $secondaryProvider)
    {
        $this->primaryProvider = $primaryProvider;
        $this->secondaryProvider = $secondaryProvider;
    }

    /**
     * @param  ProviderInterface[]  $providers
     * @return ProviderInterface
     * @throws WeatherException
     */
    public static function fromArray(array $providers): ProviderInterface
    {
        if (count($providers) < 1) {
            throw new WeatherException();
        }

        if (count($providers) === 1) {
            return $providers[0];
        }

        $secondaryProvider = array_pop($providers);
        $primaryProvider = array_pop($providers);

        $chainProvider = new ChainProvider($primaryProvider, $secondaryProvider);
        $providers[] = $chainProvider;

        return self::fromArray($providers);
    }

    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface {
        try {
            $weatherData = $this->primaryProvider->getCurrentWeatherData($latitude, $longitude, $units, $lang);
        } catch (WeatherException) {
            $weatherData = null;
        }

        return $weatherData ?? $this->secondaryProvider->getCurrentWeatherData($latitude, $longitude, $units, $lang);
    }

    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        try {
            $weatherData = $this->primaryProvider->getForecast($latitude, $longitude, $dateTime, $units, $lang);
        } catch (WeatherException) {
            $weatherData = null;
        }
        if ($weatherData !== null) {
            return $weatherData;
        }

        $weatherData = $this->secondaryProvider->getForecast($latitude, $longitude, $dateTime, $units, $lang);
        if ($weatherData !== null) {
            return $weatherData;
        }

        throw new ForecastNotPossibleException();
    }

    public function getHistorical(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        try {
            $weatherData = $this->primaryProvider->getHistorical($latitude, $longitude, $dateTime, $units, $lang);
        } catch (WeatherException) {
            $weatherData = null;
        }
        if ($weatherData !== null) {
            return $weatherData;
        }

        $weatherData = $this->secondaryProvider->getHistorical($latitude, $longitude, $dateTime, $units, $lang);
        if ($weatherData !== null) {
            return $weatherData;
        }

        throw new HistoricalDataNotAvailableException();
    }

    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        try {
            $forecastCollection = $this->primaryProvider->getForecastCollection($latitude, $longitude, $units, $lang);
        } catch (WeatherException) {
            $forecastCollection = null;
        }

        return $forecastCollection ?? $this->secondaryProvider->getForecastCollection(
                $latitude,
                $longitude,
                $units,
                $lang
            );
    }
}