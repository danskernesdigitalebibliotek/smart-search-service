<?php

namespace App\Entity;

use App\Repository\UserClickedFeedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserClickedFeedRepository::class)
 */
class UserClickedFeed
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $search;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $pid;

    /**
     * @ORM\Column(type="integer")
     */
    private int $clicks = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(string $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function getPid(): ?string
    {
        return $this->pid;
    }

    public function setPid(string $pid): self
    {
        $this->pid = $pid;

        return $this;
    }

    public function getClicks(): ?int
    {
        return $this->clicks;
    }

    public function setClicks(int $clicks): self
    {
        $this->clicks = $clicks;

        return $this;
    }

    public function incriminateClicks(int $clicks): self
    {
        $this->clicks += $clicks;

        return $this;
    }
}
