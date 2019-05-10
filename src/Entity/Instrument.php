<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="instruments")
 * @ORM\Entity(repositoryClass="App\Repository\InstrumentRepository")
 */
class Instrument
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=21)
     */
    private $symbol;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OHLCVHistory", mappedBy="symbol", orphanRemoval=true)
     */
    private $oHLCVHistories;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OHLCVQuote", mappedBy="symbol", orphanRemoval=true)
     */
    private $oHLCVQuotes;

    public function __construct()
    {
        $this->oHLCVHistories = new ArrayCollection();
        $this->oHLCVQuotes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|OHLCVHistory[]
     */
    public function getOHLCVHistories(): Collection
    {
        return $this->oHLCVHistories;
    }

    public function addOHLCVHistory(OHLCVHistory $oHLCVHistory): self
    {
        if (!$this->oHLCVHistories->contains($oHLCVHistory)) {
            $this->oHLCVHistories[] = $oHLCVHistory;
            $oHLCVHistory->setSymbol($this);
        }

        return $this;
    }

    public function removeOHLCVHistory(OHLCVHistory $oHLCVHistory): self
    {
        if ($this->oHLCVHistories->contains($oHLCVHistory)) {
            $this->oHLCVHistories->removeElement($oHLCVHistory);
            // set the owning side to null (unless already changed)
            if ($oHLCVHistory->getSymbol() === $this) {
                $oHLCVHistory->setSymbol(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|OHLCVQuote[]
     */
    public function getOHLCVQuotes(): Collection
    {
        return $this->oHLCVQuotes;
    }

    public function addOHLCVQuote(OHLCVQuote $oHLCVQuote): self
    {
        if (!$this->oHLCVQuotes->contains($oHLCVQuote)) {
            $this->oHLCVQuotes[] = $oHLCVQuote;
            $oHLCVQuote->setSymbol($this);
        }

        return $this;
    }

    public function removeOHLCVQuote(OHLCVQuote $oHLCVQuote): self
    {
        if ($this->oHLCVQuotes->contains($oHLCVQuote)) {
            $this->oHLCVQuotes->removeElement($oHLCVQuote);
            // set the owning side to null (unless already changed)
            if ($oHLCVQuote->getSymbol() === $this) {
                $oHLCVQuote->setSymbol(null);
            }
        }

        return $this;
    }
}
