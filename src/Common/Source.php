<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

class Source implements \JsonSerializable
{
    private ?string $shortName;
    private ?string $name;
    private ?string $creditUrl;

    /**
     * @param  string|null  $shortName
     * @param  string|null  $name
     * @param  string|null  $creditUrl
     */
    public function __construct(?string $shortName, ?string $name, ?string $creditUrl)
    {
        $this->shortName = $shortName;
        $this->name = $name;
        $this->creditUrl = $creditUrl;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getCreditUrl(): ?string
    {
        return $this->creditUrl;
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