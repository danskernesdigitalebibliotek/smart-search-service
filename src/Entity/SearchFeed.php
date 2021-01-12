<?php

namespace App\Entity;

use App\Repository\SearchFeedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class SearchFeed.
 *
 * @ORM\Entity(repositoryClass=SearchFeedRepository::class)
 * @ORM\Table(indexes={@ORM\Index(name="search_idx", columns={"search"})})
 */
class SearchFeed
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $year;

    /**
     * @ORM\Column(type="integer")
     */
    private int $week;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $search;

    /**
     * @ORM\Column(type="integer")
     */
    private int $longPeriod = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private int $shortPeriod = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(int $week): self
    {
        $this->week = $week;

        return $this;
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

    public function getLongPeriod(): ?int
    {
        return $this->longPeriod;
    }

    public function setLongPeriod(int $count): self
    {
        $this->longPeriod = $count;

        return $this;
    }

    public function incriminateLongPeriod(int $count): self
    {
        $this->longPeriod += $count;

        return $this;
    }

    public function getShortPeriod(): ?int
    {
        return $this->shortPeriod;
    }

    public function setShortPeriod(int $count): self
    {
        $this->shortPeriod = $count;

        return $this;
    }

    public function incriminateShortPeriod(int $count): self
    {
        $this->shortPeriod += $count;

        return $this;
    }
}
