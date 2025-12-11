<?php

namespace App\Entity;

use App\Repository\StockMovementRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StockMovementType;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: StockMovementRepository::class)]
class StockMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read:product:item'])]
    private ?int $id = null;
  
    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\ManyToOne(inversedBy: 'stockMovements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:product:item'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'stockMovements')]
    private ?User $user_id = null;

    #[ORM\ManyToOne(inversedBy: 'stockmvt')]
    private ?OrderLine $orderLine = null;

    #[ORM\Column(type:'string', enumType: StockMovementType::class)]
    #[Groups(['read:product:item'])]
    private StockMovementType $type;

    #[Groups(['read:product:item'])]
    public function getTypeValue(): string
    {
        return $this->type->value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): StockMovementType
    {
        return $this->type;
    }

    public function setType(StockMovementType $type): static
    {
        $this->type = $type;

        return $this;
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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

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

    public function getUserId(): ?User
    {
        return $this->user_id;
    }

    public function setUserId(?User $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getOrderLine(): ?OrderLine
    {
        return $this->orderLine;
    }

    public function setOrderLine(?OrderLine $orderLine): static
    {
        $this->orderLine = $orderLine;

        return $this;
    }
}
