<?php
    class ifx_Scheduler_History extends ifx_Model
    {
        public static function create($JobOrWorker, $JobWorkerId, $Message, $AdditionalData = [])
        {
            if (defined('SCHEDULER_DEBUG_MODE')) {
                print_r($Message.'<br />');
                return true;
            }

            switch (strtolower($JobOrWorker)) {
                case '0':
                case 'worker':
                    $JobOrWorker = 0;
                break;

                case '1':
                case 'job':
                    $JobOrWorker = 1;
                break;

                case '2':
                case 'test':
                    $JobOrWorker = 2;
                break;

                default:
                    throw new Exception("$JobOrWorker not valid vazlue for \$JobOrWorker", 1);

            }
            $ProcessID = isset($AdditionalData['process_id'])? $AdditionalData['process_id'] : null;

            if (empty($ProcessID)) {
                $ProcessID = isset($AdditionalData['worker']['process_id']) ? $AdditionalData['worker']['process_id'] : null;
            }

            $Model = new ifx_Scheduler_History();
            $Model->type = $JobOrWorker;
            $Model->date = time();
            $Model->type_id = $JobWorkerId;
            $Model->message = $Message;
            $Model->process_id = $ProcessID;
            $Model->additional_data = serialize($AdditionalData);

            return $Model->save();
        }
    }
