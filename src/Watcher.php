<?php
namespace Upscale\Stdlib\Object\Lifecycle;

use Exception as Stack;

class Watcher
{
    /**
     * @var string
     */
    private $probeName;

    /**
     * @var Probe[] 
     */
    private $probes = [];

    /**
     * @var int
     */
    private $probeZeroRefCount = 0;

    /**
     * @var int
     */
    private $watchCount = 0;

    /**
     * Inject dependencies
     *
     * @param string $probeName
     */
    public function __construct($probeName = '---probe')
    {
        $this->probeName = $probeName;
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
     * Start watching one or more objects
     *
     * @param object|object[] $objects
     */
    public function watch($objects)
    {
        $stack = new Stack();
        $objects = is_array($objects) ? $objects : [$objects];
        foreach ($objects as $object) {
            $this->watchCount += (int)$this->attachProbe($object, $stack);
        }
    }

    /**
     * Stop watching one or more objects
     *
     * @param object|object[] $objects
     */
    public function unwatch($objects)
    {
        $objects = is_array($objects) ? $objects : [$objects];
        foreach ($objects as $object) {
            $this->watchCount -= (int)$this->detachProbe($object);
        }
    }

    /**
     * Reference a probe from a given object incrementing the ref count of the probe
     * 
     * @param object $object
     * @param Stack $stack
     * @return bool
     */
    protected function attachProbe($object, Stack $stack)
    {
        if (!isset($object->{$this->probeName})) {
            $probe = new Probe($object, $stack);
            $this->probes[$probe->getId()] = $probe;
            if (!$this->probeZeroRefCount) {
                $this->probeZeroRefCount = $this->countReferences($probe);
            }
            $object->{$this->probeName} = $probe;
            return true;
        }
        return false;
    }

    /**
     * Remove reference to a probe from a given object decrementing the ref count of the probe
     *
     * @param object $object
     * @return bool
     */
    public function detachProbe($object)
    {
        if (isset($object->{$this->probeName})) {
            /** @var Probe $probe */
            $probe = $object->{$this->probeName};
            unset($this->probes[$probe->getId()]);
            unset($object->{$this->probeName});
            return true;
        }
        return false;
    }

    /**
     * Return the number of objects being watched including the ones that are no longer alive
     * 
     * @return int
     */
    public function countWatchedObjects()
    {
        return $this->watchCount;
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
        return count($this->detectAliveObjects($accurate));
    }

    /**
     * Detect objects that have not been destroyed since attaching the probe to them
     *
     * @param bool $accurate Force garbage collection of cyclic references before counting
     * @return array
     * @throws \UnexpectedValueException
     */
    public function detectAliveObjects($accurate = true)
    {
        if ($accurate) {
            gc_collect_cycles();
        }
        $result = [];
        $probes = $this->filterAliveProbes($this->probes);
        foreach ($probes as $probe) {
            $result[] = [
                'type'  => $probe->getOwnerType(),
                'hash'  => $probe->getOwnerHash(),
                'trace' => $probe->getStackTrace(),
            ];
        }
        // Destroy probes tracking objects that are no longer alive
        $this->probes = $probes;
        return $result;
    }

    /**
     * Free resources allocated for tracking objects that are no longer alive
     */
    public function flush()
    {
        $this->detectAliveObjects(true);
    }

    /**
     * Filter out probes tracking objects that are no longer alive
     * 
     * @param Probe[] $probes
     * @return Probe[]
     */
    protected function filterAliveProbes(array $probes)
    {
        $result = [];
        foreach ($probes as $probeId => $probe) {
            $refCount = $this->countReferences($probe) - $this->probeZeroRefCount;
            if ($refCount > 0) {
                $result[$probeId] = $probe;
            }
        }
        return $result;
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
