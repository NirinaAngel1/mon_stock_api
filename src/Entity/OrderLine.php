<?php

namespace App\Entity;

use App\Repository\OrderLineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderLineRepository::class)]
class OrderLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['order:read'])]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    #[Groups(['order:read'])]
    private ?string $unitPrice = null;

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    #[Groups(['order:read'])]
    private ?Order $order = null;

    #[ORM\ManyToOne(inversedBy: 'orderLines')]
    #[Groups(['order:read'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

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

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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
