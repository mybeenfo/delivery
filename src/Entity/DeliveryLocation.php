<?php

namespace Monastirevrf\DeliveryService\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Monastirevrf\DeliveryService\Helpers\DateTimeHelper;

/**
 * @ORM\Entity(repositoryClass="Monastirevrf\DeliveryService\Repository\DeliveryLocationRepository")delivery_locations
 * @ORM\Table(name="delivery_delivery_locations")
 * @ORM\HasLifecycleCallbacks()
 */
class DeliveryLocation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Monastirevrf\DeliveryService\Entity\Delivery", inversedBy="deliveryLocations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $delivery;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $countryName;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $countryIsoCode;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $federalDistrict;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $regionKladrId;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $regionName;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $cityKladrId;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $cityName;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $cityArea;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $cityDistrict;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $freeDeliveryFromSumm;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="Monastirevrf\DeliveryService\Entity\DeliveryLocationTariff", mappedBy="location", orphanRemoval=true)
     */
    private $deliveryLocationTariffs;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $timeZone;

    /**
     * @ORM\Column(type="time")
     */
    private $deliveryTimeFrom;

    /**
     * @ORM\Column(type="time")
     */
    private $deliveryTimeTo;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateInsert;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateUpdate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $createdBy;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updatedBy;

    public function __construct()
    {
        $this->deliveryLocationTariffs = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (!empty($this->cityArea)) {
            $field = $this->cityName . ' район ' . $this->cityArea;
        } elseif (!empty($this->cityName)) {
            $field = $this->cityName;
        } elseif (!empty($this->regionName)) {
            $field = $this->regionName;
        } elseif (!empty($this->federalDistrict)) {
            $field = $this->federalDistrict;
        } else {
            $field = $this->countryName;
        }

        return $field . ' - ' . $this->getDelivery()->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function setDelivery(?Delivery $delivery): self
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function setCountryName(string $countryName): self
    {
        $this->countryName = $countryName;

        return $this;
    }

    public function getCountryIsoCode(): ?string
    {
        return $this->countryIsoCode;
    }

    public function setCountryIsoCode(string $countryIsoCode): self
    {
        $this->countryIsoCode = $countryIsoCode;

        return $this;
    }

    public function getFederalDistrict(): ?string
    {
        return $this->federalDistrict;
    }

    public function setFederalDistrict(string $federalDistrict): self
    {
        $this->federalDistrict = $federalDistrict;

        return $this;
    }

    public function getRegionKladrId(): ?string
    {
        return $this->regionKladrId;
    }

    public function setRegionKladrId(string $regionKladrId): self
    {
        $this->regionKladrId = $regionKladrId;

        return $this;
    }

    public function getRegionName(): ?string
    {
        return $this->regionName;
    }

    public function setRegionName(string $regionName): self
    {
        $this->regionName = $regionName;

        return $this;
    }

    public function getCityKladrId(): ?string
    {
        return $this->cityKladrId;
    }

    public function setCityKladrId(?string $cityKladrId): self
    {
        $this->cityKladrId = $cityKladrId;

        return $this;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(?string $cityName): self
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getCityArea(): ?string
    {
        return $this->cityArea;
    }

    public function setCityArea(?string $cityArea): self
    {
        $this->cityArea = $cityArea;

        return $this;
    }

    public function getCityDistrict(): ?string
    {
        return $this->cityDistrict;
    }

    public function setCityDistrict(?string $cityDistrict): self
    {
        $this->cityDistrict = $cityDistrict;

        return $this;
    }

    public function getFreeDeliveryFromSumm(): ?int
    {
        return $this->freeDeliveryFromSumm;
    }

    public function setFreeDeliveryFromSumm(?int $freeDeliveryFromSumm): self
    {
        $this->freeDeliveryFromSumm = $freeDeliveryFromSumm;

        return $this;
    }


    public function getDeliveryTimeFrom(): ?\DateTimeInterface
    {
        return $this->deliveryTimeFrom;
    }

    public function setDeliveryTimeFrom(\DateTimeInterface $deliveryTimeFrom): self
    {
        $this->deliveryTimeFrom = $deliveryTimeFrom;

        return $this;
    }

    public function getDeliveryTimeTo(): ?\DateTimeInterface
    {
        return $this->deliveryTimeTo;
    }

    public function setDeliveryTimeTo(\DateTimeInterface $deliveryTimeTo): self
    {
        $this->deliveryTimeTo = $deliveryTimeTo;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }


    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $timeZone): self
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function getDateInsert(): ?\DateTimeInterface
    {
        return $this->dateInsert;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDateInsertValue()
    {
        $this->dateInsert = new \DateTime();
    }

    public function setDateInsert(?\DateTimeInterface $dateInsert): self
    {
        $this->dateInsert = $dateInsert;

        return $this;
    }

    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setDateUpdateValue()
    {
        $this->dateUpdate = new \DateTime();
    }

    public function setDateUpdate(?\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return Collection|DeliveryLocationTariff[]
     */
    public function getDeliveryLocationTariffs(): Collection
    {
        return $this->deliveryLocationTariffs;
    }

    public function addDeliveryLocationTariff(DeliveryLocationTariff $deliveryLocationTariff): self
    {
        if (!$this->deliveryLocationTariffs->contains($deliveryLocationTariff)) {
            $this->deliveryLocationTariffs[] = $deliveryLocationTariff;
            $deliveryLocationTariff->setLocationId($this);
        }

        return $this;
    }

    public function removeDeliveryLocationTariff(DeliveryLocationTariff $deliveryLocationTariff): self
    {
        if ($this->deliveryLocationTariffs->contains($deliveryLocationTariff)) {
            $this->deliveryLocationTariffs->removeElement($deliveryLocationTariff);
            // set the owning side to null (unless already changed)
            if ($deliveryLocationTariff->getLocationId() === $this) {
                $deliveryLocationTariff->setLocationId(null);
            }
        }

        return $this;
    }
}
