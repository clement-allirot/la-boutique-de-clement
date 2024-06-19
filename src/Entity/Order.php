<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use App\Twig\AppExtensions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/*
    stocker toutes les infos générales principales de commandes
 */

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $carrierName = null;

    #[ORM\Column]
    private ?float $carrierPrice = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $delivery = null;

    /**
     * @var Collection<int, OrderDetail>
     */
    #[ORM\OneToMany(targetEntity: OrderDetail::class, mappedBy: 'myOrder', cascade: ['persist'])]
    private Collection $orderDetails;

    /**
     * 1. En attente de paiement
     * 2. Paiement validé
     * 3. Expédié
     * 
     * @var integer|null
     */
    #[ORM\Column]
    private ?int $state = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function __construct()
    {
        $this->orderDetails = new ArrayCollection();
    }

    public function getTotalWt()
    {
        $totalTtc = 0;

        // pour chaque produit de ma commande, j'additionne les prix TTC
        // et je multiplie par la quantité choisie
        foreach ($this->getOrderDetails() as $orderDetail) {
            $coeff = 1 + ($orderDetail->getProductTva() / 100);
            $totalTtc += ($orderDetail->getProductPrice() * $coeff)
                * $orderDetail->getProductQuantity();
        }

        // j'ajoute le prix du transporteur
        $totalTtc += $this->getCarrierPrice();

        return number_format($totalTtc, '2', ',') . ' €';
    }

    public function getTotalTva()
    {
        $totalTva = 0;

        // pour chaque produit de ma commande, j'additionne les prix tva de chaque produit
        foreach ($this->getOrderDetails() as $orderDetail) {
            $coeff = $orderDetail->getProductTva() / 100;
            $totalTva += $orderDetail->getProductPrice() * $coeff;
        }

        return number_format($totalTva, '2', ',') . ' €';
    }

    public function getTotalHt(){
        
        $totalTvaNumber =  str_replace([' ', '€'], '', $this->getTotalTva());
        $totalWtNumber = str_replace([' ', '€'], '', $this->getTotalWt());

        $totalHt = floatval(str_replace(',', '.', $totalWtNumber)) - floatval(str_replace(',', '.', $totalTvaNumber));
        return $totalHt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCarrierName(): ?string
    {
        return $this->carrierName;
    }

    public function setCarrierName(string $carrierName): static
    {
        $this->carrierName = $carrierName;

        return $this;
    }

    public function getCarrierPrice(): ?float
    {
        return $this->carrierPrice;
    }

    public function setCarrierPrice(float $carrierPrice): static
    {
        $this->carrierPrice = $carrierPrice;

        return $this;
    }

    public function getDelivery(): ?string
    {
        return $this->delivery;
    }

    public function setDelivery(string $delivery): static
    {
        $this->delivery = $delivery;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetail>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetail(OrderDetail $orderDetail): static
    {
        if (!$this->orderDetails->contains($orderDetail)) {
            $this->orderDetails->add($orderDetail);
            $orderDetail->setMyOrder($this);
        }

        return $this;
    }

    public function removeOrderDetail(OrderDetail $orderDetail): static
    {
        if ($this->orderDetails->removeElement($orderDetail)) {
            // set the owning side to null (unless already changed)
            if ($orderDetail->getMyOrder() === $this) {
                $orderDetail->setMyOrder(null);
            }
        }

        return $this;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): static
    {
        $this->state = $state;

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
}
