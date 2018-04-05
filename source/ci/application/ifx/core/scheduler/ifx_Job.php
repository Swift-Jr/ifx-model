<?php
/* How you add a job */
//SampleJob::create($Params, $QueueName);
//SampleJob::create_at($Timestamp, $Params, $QueueName);

    class ifx_Job extends ifx_Model
    {
        const ifx_job_id = 'job_id';

        const JOB_STATE_NEW = 0;
        const JOB_STATE_QUEUED = 1;
        const JOB_STATE_RUNNING = 2;
        const JOB_STATE_COMPLETE = 3;

        public $worker = false;

        public static $DefaultQueueName = 'default';

        public function __construct($ID = null, ifx_Worker $Worker = null)
        {
            parent::__construct();
            $this->_table('ifx_job');
            $this->load($ID);
            $this->worker = $Worker;
        }

        /**
         * Get a job from the queue, assign it to the worker. Returns a job
         * or false
         *
         * @param  ifx_Worker   $Worker    [description]
         * @return ifx_Job             [description]
         */
        public static function next(ifx_Worker $Worker)
        {
            $time = time();

            $Job = new static();
            $Job->db->set('status', static::JOB_STATE_QUEUED)
                    ->set('worker_id', $Worker->id())
                    ->set('queued_time', $time)
                    ->where('queue', $Worker->queuename)
                    ->where('status', static::JOB_STATE_NEW)
                    ->where('run_after <=', $time)
                    ->order_by('run_after', 'ASC')
                    ->order_by($Job->_id(), 'ASC')
                    ->limit(1)
                    ->update($Job->_table());

            if ($Job->db->affected_rows() !== 1) {
                //No job available
                return false;
            }

            $Job = new static();
            $Job->queue = $Worker->queuename;
            $Job->status = static::JOB_STATE_QUEUED;
            $Job->worker_id = $Worker->id();
            $Job->queued_time = $time;
            $Job->db->limit(1);
            $Job->load();

            $Object = $Job->name;
            $JobPath = APPPATH."/jobs/$Object.php";

            if (file_exists($JobPath)) {
                require($JobPath);
            } else {
                ifx_Scheduler_History::create('WORKER', $Worker->id(), 'Invalid job path: '.$JobPath, $Worker->_data);
            }

            $Job = new $Object($Job->id());

            if (!$Job->is_loaded()) {
                ifx_Scheduler_History::create('WORKER', $Worker->id(), $Worker->name. ' error locating queued job', $Worker->_data);
                return false;
            }

            $Job->worker = $Worker;
            $Job->process_id = $Worker->process_id;

            return $Job;
        }

        public function begin()
        {
            $this->status = static::JOB_STATE_RUNNING;
            $this->start_time = time();
            if ($this->save()) {
                $that = $this;
                if (!is_array($this->params)) {
                    $this->params = [];
                }
                return call_user_func_array([$that, 'run'], $this->params);
            }
        }

        public function complete()
        {
            $this->status = static::JOB_STATE_COMPLETE;
            $this->end_time = time();
            return $this->save();
        }

        public function failed()
        {
            $this->retry_count++;
            $this->process_id = null;
            $this->worker_id = null;
            $this->run_after = time() + ($this->retry_count * $this->worker->job_retry_time);
            $this->status = static::JOB_STATE_NEW;
            return $this->save();
        }

        public static function create($Params = [], $QueueName = null)
        {
            !is_null($QueueName) || $QueueName = static::$DefaultQueueName;

            return static::create_job(get_called_class(), $Params, $QueueName, time());
        }

        public static function create_at($Timestamp, $Params = [], $QueueName = null)
        {
            !is_null($QueueName) || $QueueName = static::$DefaultQueueName;

            return static::create_job(get_called_class(), $Params, $QueueName, $Timestamp);
        }

        private static function create_job($JobName, $JobParams, $QueueName, $JobTime)
        {
            is_array($JobParams) || $JobParams = [];

            $Model = new static(null, new ifx_Worker());
            $Model->name = $JobName;
            $Model->params = $JobParams;
            $Model->queue = $QueueName;
            $Model->created_at = time();
            $Model->run_after = $JobTime;
            $Model->status = static::JOB_STATE_NEW;

            return $Model->save();
        }

        public function __set_params($Value)
        {
            return serialize($Value);
        }

        public function __get_params($Value)
        {
            return unserialize($Value);
        }
    }
