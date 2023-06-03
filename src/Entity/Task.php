<?php

namespace App\Entity;

use App\Repository\TachesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TachesRepository::class)]
class Taches
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Vous devez saisir un titre.")
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Vous devez saisir du contenu.")
     */
    private $content;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDone;
    
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="tasks")
     */
    private $user;


    public function getUser(): ?User
    {
        return $this->user;
    }


    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function __construct()
    {
        $this->createdAt = new \Datetime();
        $this->isDone = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        if (!is_string($title)){
            throw new \TypeError('The type must be string');
        }
        $this->title = $title;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        if (strlen($content) < 1){
            throw new \LengthException('The content must at least contain a letter');
        }
        elseif (!is_string($content)){
            throw new \TypeError('The type must be string');
        }
        $this->content = $content;
    }

    public function isDone()
    {
        return $this->isDone;
    }

    public function toggle($flag)
    {
        $this->isDone = $flag;
    }

    public function getIsDone(): ?bool
    {
        return $this->isDone;
    }

    public function setIsDone($isDone): self
    {
        if (!is_bool($isDone)){
            throw new \TypeError('The type must be boolean');
        }
        $this->isDone = $isDone;

        return $this;
    }
}
