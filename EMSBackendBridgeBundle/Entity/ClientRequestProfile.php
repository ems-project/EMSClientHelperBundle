<?php

namespace EMS\ClientHelperBundle\EMSBackendBridgeBundle\Entity;


use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class ClientRequestProfile
{
    /**
     * @var integer
     */
    protected $counter = 1;

    /**
     * @var array
     */
    protected $profiles = array();

    /**
     * @var Stopwatch $stopwatch
     */
    protected $stopwatch;

    public function __construct(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    public function getData()
    {
        return $this->profiles;
    }

    /**
     * Starts profiling
     *
     * @param string $functionName
     * @param array $arguments
     *
     * @return StopwatchEvent
     */
    public function startProfiling($functionName, array $arguments = [])
    {
        $this->profiles[$this->counter] = array(
            'name'         => $functionName,
            'arguments'    => $arguments,
            'duration'     => null,
            'memory_start' => memory_get_usage(true),
            'memory_end'   => null,
            'memory_peak'  => null,
        );

        return $this->stopwatch->start($functionName);
    }

    /**
     * Stops the profiling
     *
     * @param StopwatchEvent $event A stopwatchEvent instance
     */
    public function stopProfiling(StopwatchEvent $event = null, $result)
    {
        if ($event) {
            $event->stop();

            $values = array(
                'result'      => $result,
                'duration'    => $event->getDuration(),
                'memory_end'  => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            );

            $this->profiles[$this->counter] = array_merge($this->profiles[$this->counter], $values);

            $this->counter++;
        }
    }
}