<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql\Backends;

use DateTime;
use DateTimeZone;
use RuntimeException;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Backend;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Message;
class MySQL implements Backend
{
    public const DB_TABLE = 'queue';
    /**
     * @var string
     */
    private $table_name;
    /**
     * @var int Seconds before a dequeued item is nack'ed (if timed out) or deleted (if complete)
     */
    private $ttl = 300;
    public function __construct()
    {
        global $wpdb;
        $table_name = $wpdb->base_prefix . self::DB_TABLE;
        /**
         * Filter the table name used for queues on this backend.
         *
         * @param string $table_name Table name with $wpdb->prefix added
         */
        $table_name = (string) \apply_filters('tribe/queues/mysql/table_name', $table_name);
        $this->table_name = $table_name;
    }
    public function enqueue(string $queue_name, Message $message)
    {
        global $wpdb;
        $data = $this->prepare_data($message);
        $data['queue'] = $queue_name;
        return $wpdb->insert($this->table_name, $data);
    }
    private function prepare_data($message)
    {
        $args = $message->get_args();
        $run_after = '0000-00-00 00:00:00';
        if (isset($args['run_after'])) {
            $run_after = $args['run_after'];
            unset($args['run_after']);
        }
        return ['task_handler' => $message->get_task_handler(), 'args' => \json_encode($args), 'priority' => $message->get_priority(), 'run_after' => $run_after, 'taken' => 0, 'done' => 0];
    }
    /**
     * @param string $queue_name
     *
     * @return Message
     *
     * @throws \RuntimeException
     */
    public function dequeue(string $queue_name) : Message
    {
        global $wpdb;
        $queue = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name}\n\t\t\t\tWHERE queue = %s\n\t\t\t\tAND taken = 0\n\t\t\t\tAND done = 0\n\t\t\t\tAND run_after <= UTC_TIME()\n\t\t\t\tORDER BY priority ASC\n\t\t\t\tLIMIT 0,1\n\t\t\t\t", $queue_name), 'ARRAY_A');
        if (empty($queue)) {
            throw new RuntimeException('No messages available to reserve.');
        }
        $queue['args'] = \json_decode($queue['args'], \true);
        if (!\is_array($queue['args'])) {
            // No args, or error decoding args, leaving us
            // with an unprocessable record. Mark it complete
            // so we don't come back to it on the next run.
            $wpdb->update($this->table_name, ['taken' => \time(), 'done' => \time()], ['id' => $queue['id'], 'taken' => 0]);
            throw new RuntimeException('Unprocessable record');
        }
        $wpdb->update($this->table_name, ['taken' => \time()], ['id' => $queue['id'], 'taken' => 0]);
        if (0 === $wpdb->rows_affected) {
            throw new RuntimeException('All messages have been reserved.');
        }
        return new Message($queue['task_handler'], $queue['args'], (int) $queue['priority'], $queue['id']);
    }
    public function ack(string $job_id, string $queue_name)
    {
        global $wpdb;
        $wpdb->update($this->table_name, ['done' => \time()], ['id' => $job_id]);
    }
    public function nack(string $job_id, string $queue_name)
    {
        global $wpdb;
        $priority = $this->get_priority($job_id);
        $wpdb->update($this->table_name, ['taken' => 0, 'priority' => $priority + 1, 'run_after' => (new DateTime(\sprintf('+%d seconds', \absint($priority)), new DateTimeZone('UTC')))->format('Y-m-d H:i:s')], ['id' => $job_id]);
    }
    public function cleanup()
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->table_name} WHERE done != 0 AND done < %d", \time() - $this->ttl));
        $stale = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$this->table_name}\n\t\t\t\tWHERE taken < %d", \time() - $this->ttl));
        foreach ($stale as $id) {
            $this->nack($id, 'null');
        }
    }
    public function get_type() : string
    {
        return self::class;
    }
    public function count(string $queue_name) : int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE queue = %s AND done = 0", $queue_name));
    }
    public function table_exists()
    {
        global $wpdb;
        $table_exists = $wpdb->query($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . MySQL::DB_TABLE));
        return $table_exists ?: \false;
    }
    public function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . MySQL::DB_TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        $query = "CREATE TABLE {$table_name} (\n\t\t\t\t\tid bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,\n\t\t\t\t\tqueue varchar(255) NOT NULL,\n\t\t\t\t\ttask_handler varchar(255) NOT NULL,\n\t\t\t\t\targs text NOT NULL,\n\t\t\t\t\tpriority int(3),\n\t\t\t\t\trun_after datetime NOT NULL DEFAULT '0000-00-00 00:00:00',\n\t\t\t\t\ttaken int(10) NOT NULL DEFAULT 0,\n\t\t\t\t\tdone int(10) DEFAULT 0\n\t\t\t\t) {$charset_collate}";
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        return \dbDelta($query);
    }
    private function get_priority($task_id) : int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT priority FROM {$this->table_name} WHERE id = %s", $task_id));
    }
    /**
     * @action tribe/project/queues/mysql/init_table
     *
     * @return array|bool
     */
    public function initialize_table()
    {
        if ($this->table_exists()) {
            return \false;
        }
        return $this->create_table();
    }
}
