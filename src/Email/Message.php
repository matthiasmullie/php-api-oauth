<?php

namespace MatthiasMullie\ApiOauth\Email;

use Stampie\IdentityInterface;

class Message extends \Stampie\Message
{
    /**
     * @var IdentityInterface|string
     */
    protected $from;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @param IdentityInterface|string $to
     * @param IdentityInterface|string $from
     * @param string $subject
     */
    public function __construct($to, $from, string $subject)
    {
        parent::__construct($to);
        $this->from = $from;
        $this->subject = $subject;
    }

    /**
     * @return IdentityInterface|string
     */
    public function getFrom() {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getSubject(): string {
        return $this->subject;
    }
}
