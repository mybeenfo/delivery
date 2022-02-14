<?php

namespace Monastirevrf\DeliveryService\Entity;

use Doctrine\ORM\Mapping as ORM;
use Monastirevrf\DeliveryService\Helpers\DateTimeHelper;

/**
 * @ORM\Entity(repositoryClass="Monastirevrf\DeliveryService\Repository\DeliveryLocationTariffRepository")
 * @ORM\Table(name="delivery_delivery_tariffs")
 * @ORM\HasLifecycleCallbacks()
 */
class DeliveryLocationTariff
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Monastirevrf\DeliveryService\Entity\DeliveryLocation", inversedBy="deliveryLocationTariffs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $location;

    /**
     * @ORM\Column(type="bigint")
     */
    private $tradePointId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $pricePerKilometer;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $costRulesDeliveryByRadius = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $calculationByRadius;

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

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $active;

    /**
     * @ORM\Column(type="integer")
     */
    private $minDeliveryPrice;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $deliveryTime;

    /**
     * @ORM\Column(type="float")
     */
    private $tradePointLatitude;

    /**
     * @ORM\Column(type="float")
     */
    private $tradePointLongitude;

    /**
     * @ORM\Column(type="string")
     */
    private $tradePointPostCode;

    /**
     * @ORM\Column(type="time", nullable=true)
     */
    private $orderPickupTime;

    /**
     * @var bool
     */
    private $available = false;

    /**
     * @var \DateTime
     */
    private $deliveryDateTime;

    /**
     * @var \DateTime
     */
    private $tradePointsWithReadyTime;

    /**
     * @var float
     */
    private $deliveryPrice;

    /**
     * @var float
     */
    private $deliveryDistance;

    /**
     * @var float
     */
    private $costOfDeliveryByRadius;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tradePointAddress;

    /**
     * @ORM\Column(type="time")
     */
    private $workOfTradePointWith;

    /**
     * @ORM\Column(type="time")
     */
    private $workOfTradePointOn;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocation(): ?DeliveryLocation
    {
        return $this->location;
    }

    public function setLocation(?DeliveryLocation $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getTradePointId(): ?int
    {
        return $this->tradePointId;
    }

    public function setTradePointId(int $tradePointId): self
    {
        $this->tradePointId = $tradePointId;

        return $this;
    }


    public function getTradePointAddress(): ?string
    {
        return $this->tradePointAddress;
    }

    public function setTradePointAddress(?string $tradePointAddress): self
    {
        $this->tradePointAddress = $tradePointAddress;

        return $this;
    }

    public function getPricePerKilometer(): ?int
    {
        return $this->pricePerKilometer;
    }

    public function setPricePerKilometer(int $pricePerKilometer): self
    {
        $this->pricePerKilometer = $pricePerKilometer;

        return $this;
    }

    public function getCostRulesDeliveryByRadius(): ?array
    {
        return $this->costRulesDeliveryByRadius;
    }

    public function setCostRulesDeliveryByRadius(?array $costRulesDeliveryByRadius): self
    {
        $this->costRulesDeliveryByRadius = $costRulesDeliveryByRadius;

        return $this;
    }

    public function getCalculationByRadius(): ?bool
    {
        return $this->calculationByRadius;
    }

    public function setCalculationByRadius(bool $calculationByRadius): self
    {
        $this->calculationByRadius = $calculationByRadius;

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

    public function setUpdatedBy(int $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

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

    public function getMinDeliveryPrice(): ?int
    {
        return $this->minDeliveryPrice;
    }

    public function setMinDeliveryPrice(int $minDeliveryPrice): self
    {
        $this->minDeliveryPrice = $minDeliveryPrice;

        return $this;
    }

    public function getDeliveryTime(): ?int
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(int $deliveryTime): self
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    public function getTradePointLatitude()
    {
        return $this->tradePointLatitude;
    }

    public function setTradePointLatitude($tradePointLatitude): self
    {
        $this->tradePointLatitude = $tradePointLatitude;

        return $this;
    }

    public function getTradePointLongitude()
    {
        return $this->tradePointLongitude;
    }

    public function setTradePointLongitude($tradePointLongitude): self
    {
        $this->tradePointLongitude = $tradePointLongitude;

        return $this;
    }

    public function getTradePointPostCode(): ?string
    {
        return $this->tradePointPostCode;
    }

    public function setTradePointPostCode(string $tradePointPostCode): self
    {
        $this->tradePointPostCode = $tradePointPostCode;

        return $this;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }

    public function getAvailable(): ?bool
    {
        return $this->available;
    }

    public function setDeliveryDateTime(\DateTime $deliveryDateTime): self
    {
        $this->deliveryDateTime = $deliveryDateTime;

        return $this;
    }

    public function getDeliveryDateTime(): ?\DateTime
    {
        return $this->deliveryDateTime;
    }

    public function setTradePointsWithReadyTime(\DateTime $deliveryDateTime): self
    {
        $this->tradePointsWithReadyTime = $deliveryDateTime;

        return $this;
    }

    public function getTradePointsWithReadyTime(): ?\DateTime
    {
        return $this->tradePointsWithReadyTime;
    }

    public function setDeliveryPrice(float $deliveryPrice): self
    {
        $this->deliveryPrice = $deliveryPrice;

        return $this;
    }

    public function getDeliveryPrice(): float
    {
        return $this->deliveryPrice;
    }

    public function setDeliveryDistance(float $deliveryDistance): self
    {
        $this->deliveryDistance = $deliveryDistance;

        return $this;
    }

    public function getDeliveryDistance(): float
    {
        return $this->deliveryDistance;
    }

    public function setCostOfDeliveryByRadius(float $costOfDeliveryByRadius): self
    {
        $this->costOfDeliveryByRadius = $costOfDeliveryByRadius;

        return $this;
    }

    public function getCostOfDeliveryByRadius(): float
    {
        return $this->costOfDeliveryByRadius;
    }

    public function getWorkOfTradePointWith(): ?\DateTimeInterface
    {
        return $this->workOfTradePointWith;
    }

    public function setWorkOfTradePointWith(\DateTimeInterface $workOfTradePointWith): self
    {
        $this->workOfTradePointWith = $workOfTradePointWith;

        return $this;
    }

    public function getWorkOfTradePointOn(): ?\DateTimeInterface
    {
        return $this->workOfTradePointOn;
    }

    public function setWorkOfTradePointOn(\DateTimeInterface $workOfTradePointOn): self
    {
        $this->workOfTradePointOn = $workOfTradePointOn;

        return $this;
    }

    public function getOrderPickupTime(): ?\DateTimeInterface
    {
        return $this->orderPickupTime;
    }

    public function setOrderPickupTime(?\DateTimeInterface $orderPickupTime): self
    {
        $this->orderPickupTime = $orderPickupTime;

        return $this;
    }
}
