<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: 'username')]
#[ApiResource(
    itemOperations: ['get', 'put'],
    denormalizationContext: [
        'groups' => 'user:write',
    ],
    normalizationContext: [
        'groups' => 'user:read',
    ],
)]
#[UniqueEntity(fields: 'email')]
#[ApiFilter(PropertyFilter::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups('user:read')]
    private int $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups([
        'user:read',
        'user:write',
    ])]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    #[Groups(['user:write'])]
    private string $password;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups([
        'user:read',
        'user:write',
        'cheese_listing:item:get',
        'cheese_listing:write',
    ])]
    #[Assert\NotBlank]
    private string $username;

    #[ORM\OneToMany(
        mappedBy: 'owner',
        targetEntity: CheeseListing::class,
        cascade: ['persist'],
        orphanRemoval: true
    )]
    #[Groups(['user:read', 'user:write'])]
    #[Assert\Valid]
    private Collection $cheeseListings;

    #[Pure]
    public function __construct()
    {
        $this->cheeseListings = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection<int, CheeseListing>
     */
    public function getCheeseListings(): Collection
    {
        return $this->cheeseListings;
    }

    public function addCheeseListing(CheeseListing $cheeseListing): self
    {
        if (!$this->cheeseListings->contains($cheeseListing)) {
            $this->cheeseListings[] = $cheeseListing;
            $cheeseListing->setOwner($this);
        }

        return $this;
    }

    public function removeCheeseListing(CheeseListing $cheeseListing): self
    {
        // set the owning side to null (unless already changed)
        if ($this->cheeseListings->removeElement($cheeseListing) && $cheeseListing->getOwner() === $this) {
            $cheeseListing->setOwner(null);
        }

        return $this;
    }
}
