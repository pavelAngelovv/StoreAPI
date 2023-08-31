<?php

namespace App\Entity;

use App\Repository\AlcoholRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AlcoholRepository::class)]
class Alcohol
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Alcohol name is required.')]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Alcohol type is required.')]
    #[Assert\Choice(choices: ['beer', 'wine', 'whiskey', 'vodka', 'rum'], message: 'Invalid alcohol type.')]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Alcohol description is required.')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Producer::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Alcohol producer is required.')]
    private ?Producer $producer = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'Alcohol ABV is required.')]
    #[Assert\Type(type: 'float', message: 'ABV should be a float.')]
    private ?float $abv = null;

    #[ORM\OneToOne(targetEntity: Image::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Alcohol image is required.')]
    private ?Image $image = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProducer(): ?Producer
    {
        return $this->producer;
    }

    public function setProducer(?Producer $producer): self
    {
        $this->producer = $producer;

        return $this;
    }

    public function getAbv(): ?float
    {
        return $this->abv;
    }

    public function setAbv(float $abv): self
    {
        $this->abv = $abv;

        return $this;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function setImage(?Image $image): self
    {
        $this->image = $image;

        return $this;
    }
}
