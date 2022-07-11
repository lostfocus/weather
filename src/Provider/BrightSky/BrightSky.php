<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\BrightSky;


use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\ProviderInterface;
use Lostfocus\Weather\Common\WeatherDataCollection;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\HistoricalDataNotAvailableException;
use Lostfocus\Weather\Exceptions\WeatherException;

class BrightSky extends AbstractProvider
{

    /**
     * @throws WeatherException
     */
    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface {
        $query = [
            'lat' => $latitude,
            'lon' => $longitude,
            'units' => $this->mapUnits($units),
        ];
        $queryString = sprintf('https://api.brightsky.dev/current_weather?%s', http_build_query($query));

        $weatherRawData = $this->getArrayFromQueryString($queryString);

        return $this->mapRawData(
            $latitude,
            $longitude,
            $weatherRawData['weather'],
            WeatherDataInterface::CURRENT
        );
    }

    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        // TODO: Implement getForecast() method.
    }

    /**
     * @throws WeatherException
     */
    public function getHistorical(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        $query = [
            'date' => $dateTime->format('c'),
            'lat' => $latitude,
            'lon' => $longitude,
            'units' => $this->mapUnits($units),
        ];
        $queryString = sprintf('https://api.brightsky.dev/weather?%s', http_build_query($query));

        $weatherRawData = $this->getArrayFromQueryString($queryString);

        $weatherDataCollection = new WeatherDataCollection();

        foreach ($weatherRawData['weather'] as $weatherHourRawData) {
            $weatherDataCollection->add($this->mapRawData($latitude, $longitude, $weatherHourRawData));

        }

        $closets = $weatherDataCollection->getClosest($dateTime);
        if ($closets === null) {
            throw new HistoricalDataNotAvailableException();
        }

        return $closets;
    }

    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        // TODO: Implement getForecastCollection() method.
    }

    private function mapUnits(string $units): string
    {
        if ($units === ProviderInterface::UNIT_IMPERIAL) {
            return 'si';
        }

        return 'dwd';
    }

    private function mapRawData(
        float $latitude,
        float $longitude,
        array $rawData,
        ?string $type = null
    ): BrightSkyData {
        $weatherData = (new BrightSkyData())
            ->setLatitude($latitude)
            ->setLongitude($longitude);

        $utcDateTime = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        $utcDateTime->setTimestamp(strtotime($rawData['timestamp']));

        $weatherData->setUtcDateTime($utcDateTime);
        $weatherData->setType($type);
        if ($weatherData->getType() === null) {
            $now = new DateTime();
            if ($weatherData->getUtcDateTime() > $now) {
                $weatherData->setType(WeatherDataInterface::FORECAST);
            } else {
                $weatherData->setType(WeatherDataInterface::HISTORICAL);
            }
        }

        $weatherData->setTemperature($rawData['temperature'])
            ->setHumidity($rawData['relative_humidity'] / 100)
            ->setPressure($rawData['pressure_msl']);
        if (array_key_exists('wind_speed', $rawData)) {
            $weatherData->setWindSpeed($rawData['wind_speed']);
        } elseif (array_key_exists('wind_speed_10', $rawData)) {
            $weatherData->setWindSpeed($rawData['wind_speed_10']);
        }
        if (array_key_exists('wind_direction', $rawData)) {
            $weatherData->setWindDirection($rawData['wind_direction']);
        } elseif (array_key_exists('wind_direction_10', $rawData)) {
            $weatherData->setWindDirection($rawData['wind_direction_10']);
        }
        if (array_key_exists('precipitation', $rawData)) {
            $weatherData->setPrecipitation($rawData['precipitation']);
        } elseif (array_key_exists('precipitation_10', $rawData)) {
            $weatherData->setPrecipitation($rawData['precipitation_10']);
        }
        $weatherData->setCloudCover($rawData['cloud_cover']);

        return $weatherData;
    }
}