<?php

namespace App\Entity;

use App\Repository\BlogArticleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=BlogArticleRepository::class)
 */
class BlogArticle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"read_blog_article"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"read_blog_article"})
     */
    private $authorId;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"read_blog_article"})
     * @Assert\NotBlank(message="The title should not be blank.")
     *  @Assert\Length(
     *     max = 100,
     *     maxMessage = "The title cannot be longer than {{ limit }} characters."
     * )
     */
    private $title;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read_blog_article"})
     */
    private $publicationDate;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"read_blog_article"})
     */
    private $creationDate;

    /**
     * @ORM\Column(type="text")
     * @Groups({"read_blog_article"})
     * @Assert\NotBlank(message="The content should not be blank.")
     */
    private $content;

    /**
     * @ORM\Column(type="json")
     * @Groups({"read_blog_article"})
     */
    private $keywords = [];

    /**
     * @ORM\Column(type="string", length=20)
     * @Groups({"read_blog_article"})
     * @Assert\Choice(choices={"draft", "published", "deleted"}, message="Choose a valid status: draft, published or deleted.")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"read_blog_article"})
     * @Assert\NotBlank(message="The slug should not be blank.")
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"read_blog_article"})
     */
    private $coverPictureRef;

    public function __construct()
    {
        $this->creationDate  = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function setAuthorId(int $authorId): self
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeInterface
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(\DateTimeInterface $publicationDate): self
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCoverPictureRef(): ?string
    {
        return $this->coverPictureRef;
    }

    public function setCoverPictureRef(string $coverPictureRef): self
    {
        $this->coverPictureRef = $coverPictureRef;

        return $this;
    }
}
