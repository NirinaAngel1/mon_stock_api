<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read', 'category:read_products'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['product:read', 'product:write', 'category:read_products'])]
    #[Assert\NotBlank(message:'champs nom de produit obligatoire.')]
    #[Assert\Length(min:3,max:255, minMessage:'Le nom doit contenir 3 caractères minimum', maxMessage:'Nom de produit trop long')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read', 'product:write','category:read_products'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['product:read', 'product:write', 'category:read_products'])]
    #[Assert\NotBlank(message: "Le prix est obligatoire.")] // Contrainte ajoutée
    #[Assert\Type(type: 'numeric', message: "Le prix doit être un nombre.")]
    #[Assert\PositiveOrZero(message: "Le prix ne peut pas être négatif.")]
    private ?float $price = null;

    // #[ORM\Column]
    // // #[Groups(['product:read', 'product:write', 'category:read_products'])]
    // // #[Assert\NotBlank(message: "La quantité est obligatoire.")] // Contrainte ajoutée
    // // #[Assert\Type(type: 'integer', message: "La quantité doit être un nombre entier.")]
    // // #[Assert\PositiveOrZero(message: "La quantité en stock ne peut pas être négative.")]
    // private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read', 'category:read'])]
    private ?Category $category = null;

    /**
     * @var Collection<int, StockMovement>
     */
    #[ORM\OneToMany(targetEntity: StockMovement::class, mappedBy: 'product')]
    #[Groups(['product:read:item'])]
    private Collection $stockMovements;

    /**
     * @var Collection<int, OrderLine>
     */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'product_id')]
    private Collection $orderLines;

    #[Groups(['product:read', 'category:read_products'])]
    private ?int $currentStock = null;

    public function __construct()
    {
        $this->stockMovements = new ArrayCollection();
        $this->orderLines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    // public function getQuantity(): ?int
    // {
    //     return $this->quantity;
    // }

    // public function setQuantity(int $quantity): static
    // {
    //     $this->quantity = $quantity;

    //     return $this;
    // }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, StockMovement>
     */
    public function getStockMovements(): Collection
    {
        return $this->stockMovements;
    }

    public function addStockMovement(StockMovement $stockMovement): static
    {
        if (!$this->stockMovements->contains($stockMovement)) {
            $this->stockMovements->add($stockMovement);
            $stockMovement->setProduct($this);
        }

        return $this;
    }

    public function removeStockMovement(StockMovement $stockMovement): static
    {
        if ($this->stockMovements->removeElement($stockMovement)) {
            // set the owning side to null (unless already changed)
            if ($stockMovement->getProduct() === $this) {
                $stockMovement->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OrderLine>
     */
    public function getOrderLines(): Collection
    {
        return $this->orderLines;
    }

    public function addOrderLine(OrderLine $orderLine): static
    {
        if (!$this->orderLines->contains($orderLine)) {
            $this->orderLines->add($orderLine);
            $orderLine->setProductId($this);
        }

        return $this;
    }

    public function removeOrderLine(OrderLine $orderLine): static
    {
        if ($this->orderLines->removeElement($orderLine)) {
            // set the owning side to null (unless already changed)
            if ($orderLine->getProductId() === $this) {
                $orderLine->setProductId(null);
            }
        }

        return $this;
    }

    public function getCurrentStock(): ?int
    {
        return $this->currentStock;
    }

    public function setCurrentStock(?int $currentStock): self
    {
        $this->currentStock = $currentStock;

        return $this;
    }
}
