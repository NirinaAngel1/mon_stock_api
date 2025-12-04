<?php

namespace App\Entity;

use App\Repository\OrderLineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $unitPrice = null;

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    private ?Order $order_id = null;

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    private ?Product $product_id = null;

    /**
     * @var Collection<int, StockMovement>
     */
    #[ORM\OneToMany(targetEntity: StockMovement::class, mappedBy: 'orderLine')]
    private Collection $stockmvt;

    public function __construct()
    {
        $this->stockmvt = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getOrderId(): ?Order
    {
        return $this->order_id;
    }

    public function setOrderId(?Order $order_id): static
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getProductId(): ?Product
    {
        return $this->product_id;
    }

    public function setProductId(?Product $product_id): static
    {
        $this->product_id = $product_id;

        return $this;
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function getStockmvt(): Collection
    {
        return $this->stockmvt;
    }

    public function addStockmvt(StockMovement $stockmvt): static
    {
        if (!$this->stockmvt->contains($stockmvt)) {
            $this->stockmvt->add($stockmvt);
            $stockmvt->setOrderLine($this);
        }

        return $this;
    }

    public function removeStockmvt(StockMovement $stockmvt): static
    {
        if ($this->stockmvt->removeElement($stockmvt)) {
            // set the owning side to null (unless already changed)
            if ($stockmvt->getOrderLine() === $this) {
                $stockmvt->setOrderLine(null);
            }
        }

        return $this;
    }
}
