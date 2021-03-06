<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use JMS\Serializer\Annotation\Groups;
use Hateoas\Configuration\Annotation as Hateoas;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 * @Hateoas\Relation(
 *     name = "self",
 *     href = @Hateoas\Route(
 *         "api_product_details",
 *         parameters = { "id" = "expr(object.getId())" },
 *         absolute = true
 *     ),
 *     attributes = {"actions": { "read": "GET" }},
 *     exclusion = @Hateoas\Exclusion(groups = {"product:list", "product:details"})
 * )
 * @Hateoas\Relation(
 *     name = "all",
 *     href = @Hateoas\Route(
 *         "api_product_list",
 *         absolute = true
 *     ),
 *     attributes = {"actions": { "read": "GET" }},
 *     exclusion = @Hateoas\Exclusion(groups = {"product:details"})
 * )
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"product:list"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="You must provide a product name")
     * @Assert\Length(min=3, minMessage="The name must contain at least {{ limit }} characters")
     * @Groups({"product:list", "product:details"})
     */
    private $name;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"product:details"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"product:details"})
     */
    private $description;

    /**
     * @ORM\Column(type="float")
     * @Groups({"product:list", "product:details"})
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"product:details"})
     */
    private $color;

    /**
     * @ORM\Column(type="integer")
     * @Groups("product:list", "product:details")
     */
    private $availableQuantity;

    /**
     * Getters and setters
     */

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getAvailableQuantity(): ?int
    {
        return $this->availableQuantity;
    }

    public function setAvailableQuantity(int $availableQuantity): self
    {
        $this->availableQuantity = $availableQuantity;

        return $this;
    }
}
