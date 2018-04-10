<?php
/* How you add a job */
//SampleJob::create($Params, $QueueName);
//SampleJob::create_at($Timestamp, $Params, $QueueName);
    require_once('scheduler/ifx_Worker.php');
    require_once('scheduler/ifx_Job.php');
    require_once('scheduler/ifx_Scheduler_History.php');

    class ifx_Scheduler extends ifx_Controller
    {
        public function __construct()
        {
            parent::__construct();

            echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" />';
            echo '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>';
            echo '<style type="text/css">
                  table { margin: 20px 0; }
                  table form { margin:0; }
                  table thead button { -webkit-appearance:none!important; border:0; text-align:left; padding:0; width:100%; }
                  table tfoot ul {display:inline-block; padding:0;}
                  table tfoot ul li {display:inline-block;}
                  button .fa { float:right;}
              </style>';
            if (!defined('static::SECRET') || strlen(static::SECRET) == 0) {
                die('ifx_Scheduler::SECRET must be set.');
            }
        }

        public function index()
        {
            redirect(autoUrl('status'));
        }

        /**
         * CRON Job for the worker scheduler.
         * @return [type] [description]
         */
        public function status()
        {
            echo '<h1>Worker Status</h1>';

            //Check and show the status of each worker
            $Model = new ifx_Worker();
            /**
             * Would be nice to show the current job
             * $Model->db->where_in('job.statuster', [JOB_STATE_QUEUED, JOB_STATE_RUNNING])
             * $Workers = $Model->with('job')->fetch()
             */
            $Workers = $Model->fetch();
            $Sockets = [];

            foreach ($Workers as $Worker) {
                if (!$Worker->enabled) {
                    continue;
                }

                //Auto restart any workers that might be dead
                if ($Worker->last_updated < (time() - $Worker->maximum_job_time)) {
                    ifx_Scheduler_History::create('WORKER', $Worker->id(), $Worker->name. ' worker beginning auto-recover.', $Worker->_data);

                    //Reset the worker
                    $Worker->stop();
                    sleep($Worker->wait_no_job_available);

                    //Reset any open job
                    $Worker->recover_lost_jobs();

                    //Restart the worker
                    $Worker->enable();
                }

                //Run the worker - this process self ensures single threading for each worker
                $WorkerURL = autoUrl('run_worker/'.static::SECRET.'/'.$Worker->id().'/'.rawurlencode($Worker->name));
                $WorkerURL = site_url($WorkerURL);

                $URL = parse_url($WorkerURL);
                if (ENVIRONMENT == 'production') {
                    $Port = isset($URL['port']) ? $URL['port'] : 80;
                } else {
                    $Port = 9002;
                }

                $Socket = $Sockets[$Worker->name] = @fsockopen($URL['host'], $Port, $errno, $errstr, 5);

                if (!$Socket) {
                    //Raise some error
                    if ($errno == 60) {
                        //Socket timeout, ignore
                    } else {
                        echo("<p>{$Worker->name} worker failed to launch: $errstr ($errno)</p>");
                    }
                } else {
                    $out = "GET {$URL['path']} HTTP/1.1\r\n";
                    $out.= "Host: {$URL['host']}\r\n";
                    $out.= "Connection: Close\r\n\r\n";

                    fwrite($Socket, $out);
                    fclose($Socket);
                }
            }

            echo '<a href="'.autoUrl('edit_worker').'" class="btn btn-primary">New Worker</a>';

            //Finally, output a status table
            $WorkerStatus = new ifx_Table2(new ifx_Worker());

            ifx_TColumn::create('name', 'Worker Name', $WorkerStatus)
                  ->formatter(function ($Row) {
                      $url = autoUrl('/edit_worker/'.$Row->id());
                      return "<a href=\"$url\">{$Row->name}</a>";
                  });
            ifx_TColumn::create('queuename', 'Queue Name', $WorkerStatus);
            ifx_TColumn::create('status', 'Status', $WorkerStatus)
                  ->formatter(function ($Row, $Value) {
                      $Status[0] = 'Inactive';
                      $Status[1] = 'Ready';
                      $Status[2] = 'Busy';

                      return $Status[$Value];
                  });
            ifx_TColumn::create('last_updated', 'Updated', $WorkerStatus)
                  ->formatter(function ($Row, $Value) {
                      return date('H:i:s', $Row->last_updated);
                  });
            ifx_TColumn::create('memory_usage', 'Memory Usage', $WorkerStatus)
                  ->formatter(function ($Row, $Value) {
                      return number_format(round($Value/pow(1024, 1))).'kb';
                  });
            ifx_TColumn::create('jobs_queued', 'Pending', $WorkerStatus)
                  ->formatter(function ($Row) {
                      $Jobs = new ifx_Job();
                      $Jobs->db->where('queue', $Row->queuename)
                              ->where_in('status', [ifx_Job::JOB_STATE_NEW, ifx_Job::JOB_STATE_QUEUED])
                              ->where('run_after <=', time());
                      return $Jobs->count();
                      ;
                  });
            ifx_TColumn::create('jobs_delayed', 'Delayed', $WorkerStatus)
                  ->formatter(function ($Row) {
                      $Jobs = new ifx_Job();
                      $Jobs->db->where('queue', $Row->queuename)
                              ->where_in('status', [ifx_Job::JOB_STATE_NEW, ifx_Job::JOB_STATE_QUEUED])
                              ->where('run_after >', time())
                              ->where('retry_count <=', 5);
                      return $Jobs->count();
                      ;
                  });
            ifx_TColumn::create('jobs_failing', 'Failing', $WorkerStatus)
                  ->formatter(function ($Row) {
                      $Jobs = new ifx_Job();
                      $Jobs->db->where('queue', $Row->queuename)
                              ->where_in('status', [ifx_Job::JOB_STATE_NEW, ifx_Job::JOB_STATE_QUEUED])
                              ->where('retry_count >', 5);
                      return $Jobs->count();
                      ;
                  });
            ifx_TColumn::create('jobs_complete', 'Complete', $WorkerStatus)
                  ->formatter(function ($Row) {
                      $Jobs = new ifx_Job();
                      $Jobs->db->where('queue', $Row->queuename)
                              ->where('worker_id', $Row->id())
                              ->where('status', ifx_Job::JOB_STATE_COMPLETE);
                      return $Jobs->count();
                      ;
                  });
            ifx_TColumn::create('enabled', 'Enabled', $WorkerStatus)
                  ->formatter(function ($Row, $Value) {
                      if ($Value) {
                          $URL = autoUrl('stop/'.$Row->id());
                          return '<a href="'.$URL.'"><i class="fa fa-2x fa-power-off text-success"></i></a>';
                      } else {
                          $URL = autoUrl('start/'.$Row->id());
                          return '<a href="'.$URL.'"><i class="fa fa-2x fa-power-off text-danger"></i></a>';
                      }
                  });
            ifx_TColumn::create('history', 'History', $WorkerStatus)
                  ->formatter(function ($Row, $Value) {
                      $URL = autoUrl('worker_history/'.$Row->id());
                      return '<a href="'.$URL.'"><i class="fa fa-2x fa-history text-secondary"></i></a>';
                  });

            $WorkerStatus->display();

            echo '<script>setTimeout(function(){window.location.reload(1);}, 5000);</script>';
        }

        public function start($WorkerID)
        {
            $Worker = new ifx_Worker($WorkerID);

            if (!$Worker->enable()) {
                echo "<p>Failed to enable {$Worker->name}</p>";
            }

            redirect(autoUrl('status'));
        }

        public function stop($WorkerID)
        {
            $Worker = new ifx_Worker($WorkerID);

            if (!$Worker->stop()) {
                echo "<p>Failed to stop {$Worker->name}</p>";
            }

            redirect(autoUrl('status'));
        }

        public function worker_history($WorkerID)
        {
            $Worker = new ifx_Worker($WorkerID);
            //Show the job history for a worker
            $History = new ifx_Scheduler_History();
            $History->type_id = $WorkerID;
            $History->type = 0;

            $Table = new ifx_Table2($History);

            ifx_TColumn::create('date', 'Date', $Table)
                  ->formatter(function ($Row) {
                      return date('d-M-Y H:i:s', $Row->date);
                  })
                  ->defaultSort('DESC');
            ifx_TColumn::create('process_id', 'Thread', $Table);
            ifx_TColumn::create('message', 'Message', $Table);

            echo '<h1>Worker History</h1>';
            echo '<h3>'.$Worker->name.'</h3>';
            echo '<a href="'.autoUrl('status').'" class="btn btn-primary">All Workers</a>';
            $Table->display();
        }

        public function run_worker($Secret, $WorkerID, $WorkerName)
        {
            if ($Secret !== static::SECRET) {
                throw new ifx_Scheduler_Exception("Duff secret provided for $WorkerName ($WorkerID)");
            }

            ignore_user_abort(true);
            set_time_limit(0);

            $Worker = new ifx_Worker($WorkerID);
            $Worker->run();
        }

        public function run_worker_manual($Secret, $WorkerID, $WorkerName)
        {
            if ($Secret !== static::SECRET) {
                throw new ifx_Scheduler_Exception("Duff secret provided for $WorkerName ($WorkerID)");
            }

            ignore_user_abort(false);
            set_time_limit(120);

            $Worker = new ifx_Worker($WorkerID);
            $Worker->enable();
            $Worker->run();
        }

        public function test_job($Secret, $JobName)
        {
            define('SCHEDULER_DEBUG_MODE', 1);

            if ($Secret !== static::SECRET) {
                throw new Exception("Duff secret provided for $JobName ($JobName)");
            }

            ifx_Scheduler_History::create('test', 0, 'Testing sample job name:'.$JobName);

            ifx_Job::load_job_handeler($JobName);
            $Job = new $JobName();

            if ($Job->test()) {
                ifx_Scheduler_History::create('test', 0, 'TEST SUCCESS:'.$JobName, $Job->_data);
            } else {
                ifx_Scheduler_History::create('test', 0, 'TEST FAILED:'.$JobName, $Job->_data);
            }
        }

        public function edit_worker($WorkerID = null)
        {
            $Worker = new ifx_Worker($WorkerID);

            if (isset($_POST['submit'])) {
                $Worker->__post();

                if ($Worker->save()) {
                    redirect(autoUrl('status'));
                }
            }

            if (isset($_POST['delete'])) {
                if ($Worker->delete()) {
                    redirect(autoUrl('status'));
                }
            }

            $this->show_worker_form($Worker, 'Create Worker');

            $Noun = $Worker->is_loaded() ? 'Update' : 'Create';
            echo '<button type="submit" name="submit" class="btn btn-primary large">'.$Noun.'</button>';

            if ($Worker->is_loaded()) {
                echo '<button type="submit" name="delete" class="btn btn-danger large">Delete</button>';
            }

            echo '</form>';
        }

        public function show_worker_form($Worker, $FormTitle)
        {
            echo "<h1>$FormTitle</h1>";
            echo '<form method="post">';

            $Input = new ifx_Input('input', 'text');
            $Input->placeholder('Worker Name')
                  ->label('Worker Name')
                  ->name('name')
                  ->bindTo($Worker)
                  ->display();

            $Input = new ifx_Input('input', 'text');
            $Input->placeholder('Queue Name')
                  ->label('Queue Name')
                  ->name('queuename')
                  ->bindTo($Worker)
                  ->display();
            ;

            $Input = new ifx_Input('input', 'text');
            $Input->placeholder('time in seconds')
                  ->label('Maximum Execution Time')
                  ->name('maximum_job_time')
                  ->bindTo($Worker)
                  ->display();

            $Input = new ifx_Input('input', 'text');
            $Input->placeholder('time in seconds')
                  ->label('Wait time for new jobs')
                  ->name('wait_no_job_available')
                  ->bindTo($Worker)
                  ->display();

            $Input = new ifx_Input('input', 'text');
            $Input->placeholder('time in seconds')
                  ->label('Retry delay for failed jobs')
                  ->name('job_retry_time')
                  ->bindTo($Worker)
                  ->display();

            $Input = new ifx_Input('input', 'text');
            $Input->placeholder('time in seconds')
                  ->label('Minimum time between jobs starting')
                  ->name('wait_between_jobs_starting')
                  ->bindTo($Worker)
                  ->display();
        }
    }
