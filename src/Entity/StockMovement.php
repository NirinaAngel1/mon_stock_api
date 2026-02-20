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
    #[Groups(['read:product:item', 'stock:read'])]
    private ?int $id = null;
  
    #[ORM\Column]
    #[Groups(['stock:read'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['stock:read'])]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    #[ORM\ManyToOne(inversedBy: 'stockMovements')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read:product:item', 'stock:read'])]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity:User::class, inversedBy: 'stockMovements')]
    #[ORM\JoinColumn(name:'user_id', referencedColumnName:'id', nullable:true, onDelete:'SET NULL')]
    #[Groups(['stock:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'stockmvt')]
    private ?OrderLine $orderLine = null;

    #[ORM\Column(type:'string', enumType: StockMovementType::class)]
    #[Groups(['read:product:item','stock:read'])]
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
