<?php


namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use DateTime;
use JMS\Serializer\Annotation as Serializer;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @ORM\Entity
 *
 * @Serializer\XmlNamespace(uri="http://www.w3.org/2005/Atom", prefix="atom")
 * @Serializer\AccessorOrder(
 *     "custom",
 *     custom={ "id", "result", "time" }
 *     )
 *
 * @Hateoas\Relation(
 *     name="parent",
 *     href="expr(constant('\\App\\Controller\\ApiResultsController::RUTA_API'))"
 * )
 *
 * @Hateoas\Relation(
 *     name="self",
 *     href="expr(constant('\\App\\Controller\\ApiResultsQueryController::RUTA_API') ~ '/' ~ object.getId())"
 * )
 * @ORM\Table(
 *     name    = "results",
 *     indexes = {
 *          @ORM\Index(name="FK_USER_ID_idx", columns={ "user_id" })
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ResultRepository")
 */
class Result implements JsonSerializable
{
    public const RESULT_ATTR = 'result';
    public const USER_ID_ATTR = 'userId';
    public const RESULT_ID_ATTR = 'resultId';
    public const RESULT_USER_ATTR = 'user';
    public const TIME_ATTR = 'time';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Serializer\XmlAttribute
     */
    private ?int $id = 0;

    /**
     * Result value
     *
     * @ORM\Column(
     *     name     = "result",
     *     type     = "integer",
     *     nullable = false
     *     )
     * @Serializer\XmlAttribute
     */
    private int $result;

    /**
     * Result user
     *
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist", "remove" })
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(
     *          name                 = "user_id",
     *          referencedColumnName = "id",
     *          onDelete             = "cascade"
     *     )
     * })
     * @Serializer\SerializedName(Result::RESULT_USER_ATTR)
     */
    private User $user;

    /**
     * Result time
     *
     * @ORM\Column(
     *     name     = "time",
     *     type     = "datetime",
     *     nullable = false
     *     )
     *
     * @Serializer\Exclude()
     */
    private DateTime $time;

    /**
     * Result constructor.
     *
     * @param int $result result
     * @param User|null $user user
     * @param DateTime|null $time time
     */
    public function __construct(
        int       $result = 0,
        ?User     $user = null,
        ?DateTime $time = null
    )
    {
        $this->id = 0;
        $this->result = $result;
        $this->user = $user;
        $this->time = $time;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getResult(): int
    {
        return $this->result;
    }

    /**
     * @param int $result
     */
    public function setResult(int $result): void
    {
        $this->result = $result;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return DateTime
     */
    public function getTime(): DateTime
    {
        return $this->time;
    }

    /**
     * @param DateTime $time
     */
    public function setTime(DateTime $time): void
    {
        $this->time = $time;
    }

    /**
     * Implements __toString()
     *
     * @return string
     * @link   http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString(): string
    {
        return sprintf(
            '%3d - %3d - %22s - %s',
            $this->id,
            $this->result,
            $this->user->getId(),
            $this->time->format('Y-m-d H:i:s')
        );
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link   http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since  5.4.0
     */
    public function jsonSerialize(): array
    {
        return array(
            'id' => $this->id,
            'result' => $this->result,
            'user' => $this->user,
            'time' => $this->time->format('Y-m-d H:i:s')
        );
    }
}
