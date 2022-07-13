<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\BrightSky;

use DateInterval;
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
    ): ?WeatherDataInterface {
        $limitInterval = new DateInterval('P10D');

        return $this->getForecastFromCollectionWithLimit(
            $latitude,
            $longitude,
            $dateTime,
            $limitInterval,
            $units,
            $lang
        );
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
        $weatherDataCollection = $this->getWeatherDataCollection($dateTime, $latitude, $longitude, $units);

        $closets = $weatherDataCollection->getClosest($dateTime);
        if ($closets === null) {
            throw new HistoricalDataNotAvailableException();
        }

        return $closets;
    }

    /**
     * @throws WeatherException
     */
    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        $now = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        $tenDays = (clone $now)->add(new DateInterval('P10D'));

        return $this->getWeatherDataCollection($now, $latitude, $longitude, $units, $tenDays);
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

    /**
     * @param  DateTimeInterface  $dateTime
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  string  $units
     * @param  DateTimeInterface|null  $lastDateTime
     * @return WeatherDataCollection
     * @throws WeatherException
     */
    private function getWeatherDataCollection(
        DateTimeInterface $dateTime,
        float $latitude,
        float $longitude,
        string $units,
        ?DateTimeInterface $lastDateTime = null
    ): WeatherDataCollection {
        $query = [
            'date' => $dateTime->format('c'),
            'lat' => $latitude,
            'lon' => $longitude,
            'units' => $this->mapUnits($units),
        ];
        if ($lastDateTime !== null) {
            $query[' last_date'] = $lastDateTime->format('c');
        }
        $queryString = sprintf('https://api.brightsky.dev/weather?%s', http_build_query($query));

        $weatherRawData = $this->getArrayFromQueryString($queryString);

        $weatherDataCollection = new WeatherDataCollection();

        foreach ($weatherRawData['weather'] as $weatherHourRawData) {
            $weatherDataCollection->add($this->mapRawData($latitude, $longitude, $weatherHourRawData));
        }

        return $weatherDataCollection;
    }
}