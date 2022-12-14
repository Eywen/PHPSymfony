<?php

namespace App\Entity;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class Message
 *
 * @Serializer\XmlRoot(name=Message::MESSAGE_ATTR)
 * @Serializer\XmlNamespace(uri="http://www.w3.org/2005/Atom", prefix="atom")
 */
class Message
{
    public final const MESSAGE_ATTR = 'message';
    public final const CODE_ATTR = 'code';

    /**
     * Code
     *
     * @Serializer\SerializedName(Message::CODE_ATTR)
     */
    private int $code;

    /**
     * Message
     *
     * @Serializer\SerializedName(Message::MESSAGE_ATTR)
     */
    private null|string $message;

    /**
     * Message constructor.
     *
     * @param int         $code    code
     * @param null|string $message message
     */
    public function __construct(int $code = 200, ?string $message = null)
    {
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * Get code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Set code
     *
     * @param int $code code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * Get message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Set message
     *
     * @param null|string $message message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }
}
