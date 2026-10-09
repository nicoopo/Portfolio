<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Article du blog : /articles/slug, contenu en Markdown (App\Twig\Markdown).
 * Visible une fois sa date de publication passée ; sans date, c'est un brouillon.
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[UniqueEntity('slug')]
class Article
{
    use Traduisible;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Regex('/^[a-z0-9-]+$/', message: 'Minuscules, chiffres et tirets uniquement.')]
    #[ORM\Column(length: 100, unique: true)]
    private string $slug;

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[ORM\Column(length: 150)]
    private string $titre;

    /** Chapô : liste des articles, haut de l'article et description pour les moteurs de recherche */
    #[Assert\NotBlank]
    #[Assert\Length(max: 300)]
    #[ORM\Column(length: 300)]
    private string $resume;

    /** Markdown */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::TEXT)]
    private string $contenu;

    /** Image d'illustration : nom du fichier dans public/uploads/articles/ (volume Docker en prod) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /** Vide : brouillon ; dans le futur : publication programmée */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publieLe = null;

    public function getId(): ?int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function getTitre(): string { return $this->titre; }
    public function getResume(): string { return $this->resume; }
    public function getContenu(): string { return $this->contenu; }
    public function getImage(): ?string { return $this->image; }
    public function getPublieLe(): ?\DateTimeImmutable { return $this->publieLe; }

    public function setSlug(?string $slug): static { $this->slug = $slug ?? ''; return $this; }
    public function setTitre(?string $titre): static { $this->titre = $titre ?? ''; return $this; }
    public function setResume(?string $resume): static { $this->resume = $resume ?? ''; return $this; }
    public function setContenu(?string $contenu): static { $this->contenu = $contenu ?? ''; return $this; }
    public function setImage(?string $image): static { $this->image = $image ?: null; return $this; }
    public function setPublieLe(?\DateTimeImmutable $publieLe): static { $this->publieLe = $publieLe; return $this; }

    public function __toString(): string { return $this->titre; }
}
