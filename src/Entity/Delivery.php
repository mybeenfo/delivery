<?php

namespace Monastirevrf\DeliveryService\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Monastirevrf\DeliveryService\Repository\DeliveryRepository")
 * @ORM\Table(name="delivery_deliveries")
 * @ORM\HasLifecycleCallbacks()
 */
class Delivery
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $active;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sort;

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
     * @ORM\OneToMany(targetEntity="Monastirevrf\DeliveryService\Entity\DeliveryLocation", mappedBy="delivery")
     */
    private $deliveryLocations;

    /**
     * @ORM\ManyToOne(targetEntity="Monastirevrf\DeliveryService\Entity\DeliveryType", inversedBy="deliveries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $deliveryType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    public function __construct()
    {
        $this->deliveryLocations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): self
    {
        $this->sort = $sort;

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
     * @return Collection|DeliveryLocation[]
     */
    public function getDeliveryLocations(): Collection
    {
        return $this->deliveryLocations;
    }

    public function addDeliveryLocation(DeliveryLocation $deliveryLocation): self
    {
        if (!$this->deliveryLocations->contains($deliveryLocation)) {
            $this->deliveryLocations[] = $deliveryLocation;
            $deliveryLocation->setDelivery($this);
        }

        return $this;
    }

    public function removeDeliveryLocation(DeliveryLocation $deliveryLocation): self
    {
        if ($this->deliveryLocations->contains($deliveryLocation)) {
            $this->deliveryLocations->removeElement($deliveryLocation);
            // set the owning side to null (unless already changed)
            if ($deliveryLocation->getDelivery() === $this) {
                $deliveryLocation->setDelivery(null);
            }
        }

        return $this;
    }

    public function getDeliveryType(): ?DeliveryType
    {
        return $this->deliveryType;
    }

    public function setDeliveryType(?DeliveryType $deliveryType): self
    {
        $this->deliveryType = $deliveryType;

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
}
