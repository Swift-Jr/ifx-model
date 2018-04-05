<?php
    class ifx_Scheduler_History extends ifx_Model
    {
        public static function create($JobOrWorker, $JobWorkerId, $Message, $AdditionalData = [])
        {
            switch ($JobOrWorker) {
                case '0':
                case 'Worker':
                case 'WORKER':
                    $JobOrWorker = 0;
                break;
                default:
                    //job
                    $JobOrWorker = 1;
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
