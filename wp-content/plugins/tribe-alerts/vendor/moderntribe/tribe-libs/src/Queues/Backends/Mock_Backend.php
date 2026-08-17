<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues\Backends;

use RuntimeException;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Backend;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Message;
/**
 * Class Mock_Backend
 *
 * A trivial backend for use when running tests.
 */
class Mock_Backend implements Backend
{
    /**
     * @var array<string, Message[]>
     */
    private $queues = [];
    /**
     * Allow returning the internal properties for tests.
     *
     * @return array<string, Message[]>
     */
    public function queues() : array
    {
        return $this->queues;
    }
    public function enqueue(string $queue_name, Message $m)
    {
        $this->queues[$queue_name][] = $m;
    }
    /**
     * @param  string  $queue_name
     *
     * @return Message The first message in the queue. Nothing will be reserved.
     */
    public function dequeue(string $queue_name) : Message
    {
        if (\array_key_exists($queue_name, $this->queues) && !empty($this->queues[$queue_name])) {
            return \reset($this->queues[$queue_name]);
        }
        throw new RuntimeException('No messages available to reserve.');
    }
    public function ack(string $job_id, string $queue_name)
    {
        $id = (int) $job_id;
        if (!isset($this->queues[$queue_name][$id])) {
            return;
        }
        unset($this->queues[$queue_name][$id]);
    }
    public function nack(string $job_id, string $queue_name)
    {
        // does nothing
    }
    public function get_type() : string
    {
        return self::class;
    }
    public function count(string $queue_name) : int
    {
        if (\array_key_exists($queue_name, $this->queues) && !empty($this->queues[$queue_name])) {
            return \count($this->queues[$queue_name]);
        }
        return 0;
    }
    /**
     * Resets the queue, deleting everything in it
     *
     * @return void
     */
    public function cleanup()
    {
        $this->queues = [];
    }
}
