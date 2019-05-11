<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OHLCVQuoteRepository")
 */
class OHLCVQuote
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $open;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $high;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $low;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $close;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $volume;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Instrument", inversedBy="oHLCVQuotes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $symbol;

    /**
     * @ORM\Column(type="dateinterval", nullable=true)
     */
    private $timeinterval;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $provider;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpen(): ?float
    {
        return $this->open;
    }

    public function setOpen(?float $open): self
    {
        $this->open = $open;

        return $this;
    }

    public function getHigh(): ?float
    {
        return $this->high;
    }

    public function setHigh(?float $high): self
    {
        $this->high = $high;

        return $this;
    }

    public function getLow(): ?float
    {
        return $this->low;
    }

    public function setLow(float $low): self
    {
        $this->low = $low;

        return $this;
    }

    public function getClose(): ?float
    {
        return $this->close;
    }

    public function setClose(?float $close): self
    {
        $this->close = $close;

        return $this;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getSymbol(): ?Instrument
    {
        return $this->symbol;
    }

    public function setSymbol(?Instrument $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getTimeinterval(): ?\DateInterval
    {
        return $this->timeinterval;
    }

    public function setTimeinterval(?\DateInterval $timeinterval): self
    {
        $this->timeinterval = $timeinterval;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeInterface
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeInterface $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }
}
