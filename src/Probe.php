<?php
namespace Upscale\Stdlib\Object\Lifecycle;

use Exception as Stack;

class Probe
{
    /**
     * @var string
     */
    private $ownerType;

    /**
     * @var string
     */
    private $ownerHash;

    /**
     * @var string
     */
    private $stackTrace;

    /**
     * Inject dependencies
     *
     * @param object $owner
     * @param Stack $stack
     */
    public function __construct($owner, Stack $stack)
    {
        $this->ownerType = get_class($owner);
        $this->ownerHash = spl_object_hash($owner);
        $this->stackTrace = $stack->getTraceAsString();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return spl_object_hash($this);
    }

    /**
     * @return string
     */
    public function getOwnerType()
    {
        return $this->ownerType;
    }

    /**
     * @return string
     */
    public function getOwnerHash()
    {
        return $this->ownerHash;
    }

    /**
     * @return string
     */
    public function getStackTrace()
    {
        return $this->stackTrace;
    }
}
