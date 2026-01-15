<?php
class MRDates{
    private $dates;
    private $datessequence;
    private $startdate;
    private $enddate;
    private $starttime;
    private $endtime;
    public function __construct($dates){
        $this->dates = $dates;
        usort($this->dates,function($a,$b){
            if ($a['date'] == $b['date']) {
                return 0;
            }
            return ($a['date'] < $b['date']) ? -1 : 1;
        });
        $this->array_to_sequence();
    }

    public function array_to_sequence(){
        $count = count($this->dates);
        if($count==0){
            $this->dates = null;
        }
        else{
            $start = $this->dates[0]['date'];
            $end = $this->dates[$count -1]['date'];
            if($count==1||$start==$end){
                $this->datesequence = 'single';
                $this->starttime = $this->dates[0]['time_start'];
                $this->endtime = $this->dates[0]['time_end'];
            }
            else{
                $this->datesequence = 'singles';
                $this->starttime = $this->dates[0]['time_start'];
                $this->endtime = $this->dates[0]['time_end'];
                $this->dates = array_map(function($n){return ['date'=>$n['date'],'start_time'=>$n['time_start'],'end_time'=>$n['time_end']];},array_values($this->dates));
                /* $dates_array = [];
                foreach($this->dates as $i=>$date){
                    $dates_array['all_dates_'.$i.'_date']=$date['date'];
                    $dates_array['all_dates_'.$i.'_start_time']=$date['start'];
                    $dates_array['all_dates_'.$i.'_end_time']=$date['end'];
                }
                $this->dates=$dates_array; */
            }
            $this->startdate = $start;
            $this->enddate = $end;    
        }

    }

    public function get_dates(){
        if($this->dates===null){
            throw new Exception('There are no dates for this event');
        }
        $return_arr = [
            'single_dates_or_series'=>$this->datesequence,
            'start_date'=>$this->startdate,
            'end_date'=>$this->enddate,
            'times_start_time'=>$this->starttime,
            'times_end_time'=>$this->endtime
        ];
        if($this->datesequence=='singles'){
            $return_arr['dates'] = $this->dates;
        }
        return $return_arr; 
    }

}

