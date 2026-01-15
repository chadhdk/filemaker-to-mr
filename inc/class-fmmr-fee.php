<?php

class MRFee{
    private $fee_types = ['free','fee', 'scale', 'donation', 'fee_donation'];
    private $type;
    private $fee;
    private $min;
    private $max;
    private $step;
    private $single;

    public function __construct($fee,$single=false){
        $this->type = isset($fee['type'])&&in_array($fee['type'],$this->fee_types)?sanitize_text_field($fee['type']):null;
        $this->fee = isset($fee['fee'])?intval($fee['fee']):null;
        $this->min = isset($fee['min'])?intval($fee['min']):null;
        $this->max = isset($fee['max'])?intval($fee['max']):null;
        $this->step = isset($fee['step'])?intval($fee['step']):null;
        $this->single = $single?:'';
    }

    public function get_fee(){
        if(!$this->type){
            throw new Exception('Fee Type must be set');
        }
        if(($this->type=='fee'||$this->type=='fee_donation')&&!$this->fee){
            throw new Exception('Fee must be set');
        }
        if($this->type=='scale'&&(!$this->min||!$this->max||!$this->step)){
            throw new Exception('Min, Max and Step must be set');
        }
        $return_arr = [
            'details_free_or_fee'.$this->single=>$this->type
        ];
        if($this->fee){
            $return_arr['details_fee'.$this->single]=$this->fee;
        }
        if($this->min){
            $return_arr['details_sliding_scale_range_min'.$this->single]=$this->min;
        }
        if($this->max){
            $return_arr['details_sliding_scale_range_max'.$this->single]=$this->max;
        }
        if($this->step){
            $return_arr['details_sliding_scale_range_step'.$this->single]=$this->step;
        }
        return $return_arr;
    }
}