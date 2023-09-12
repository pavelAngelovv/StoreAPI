<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["alcohol"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["alcohol"])]
    private ?string $name = null;

    #[Groups(["alcohol"])]
    private ?string $url = null;

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
        $this->url = '/storage/images/' . $name;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
