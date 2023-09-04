<?php

namespace App\Entity;

use App\Repository\ProducerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProducerRepository::class)]
class Producer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["alcohol"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["alcohol"])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(["alcohol"])]
    private ?string $country = null;

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

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }
}
