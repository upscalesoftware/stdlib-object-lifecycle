<?php
namespace Upscale\Stdlib\Object\Lifecycle;

class Watcher
{
    /**
     * @var string
     */
    private $probeName;

    /**
     * @var \stdClass
     */
    private $probe;

    /**
     * @var int
     */
    private $probeZeroRefCount;

    /**
     * Inject dependencies
     *
     * @param string $probeName
     */
    public function __construct($probeName = '---probe')
    {
        $this->probeName = $probeName;
        $this->probe = new \stdClass();
        $this->probeZeroRefCount = $this->countReferences($this->probe);
    }

    /**
     * Count references to a given variable
     *
     * @param mixed $variable
     * @return int
     * @throws \UnexpectedValueException
     */
    protected function countReferences(&$variable)
    {
        ob_start();
        debug_zval_dump($variable);
        $dump = ob_get_clean();
        if (preg_match('/refcount\((\d+)\)/', $dump, $matches)) {
            return (int)$matches[1];
        }
        throw new \UnexpectedValueException('Unable to count references.');
    }

    /**
     * Reference the probe from a given object incrementing the ref count of the probe
     *
     * @param object $subject
     */
    public function watch($subject)
    {
        $subject->{$this->probeName} = $this->probe;
    }

    /**
     * Remove reference to the probe from a given object if there's one, decrementing the ref count of the probe
     *
     * @param object $subject
     */
    public function unwatch($subject)
    {
        unset($subject->{$this->probeName});
    }

    /**
     * Count objects that have not been destroyed since attaching the probe to them
     *
     * @param bool $accurate Force garbage collection of cyclic references before counting
     * @return int
     * @throws \UnexpectedValueException
     */
    public function countAliveObjects($accurate = true)
    {
        if ($accurate) {
            gc_collect_cycles();
        }
        return ($this->countReferences($this->probe) - $this->probeZeroRefCount);
    }

    /**
     * Assert that all watched objects have been since destroyed
     *
     * @throws \UnexpectedValueException
     */
    public function assertObjectsDestroyed()
    {
        $aliveCount = $this->countAliveObjects();
        if ($aliveCount > 0) {
            throw new \UnexpectedValueException(
                "A total of $aliveCount watched objects have not been destroyed."
            );
        }
    }
}
