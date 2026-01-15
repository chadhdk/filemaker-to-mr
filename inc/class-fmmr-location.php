<?php 
class MRLocation{
    private $id;
    private $name;
    private $filemaker_id;
    private $booking;

    public $status;
    public $response;

    public function __construct($location){
        if(isset($location['id'])){
            $this->id = intval($location['id']);
        }
        if(isset($location['name'])){
            $this->name = sanitize_text_field($location['name']);
        }
        if(isset($person['filemaker_id'])){
            $this->filemaker_id = intval($person['filemaker_id']);
        }
        if(isset($location['booking'])){
            $this->booking= $location['booking'];
        }
    }

    public function get_location(){
        if(!isset($this->id)&&!isset($this->name)){
            throw new Exception('Location requires a name or an ID');
        }
        if($this->id && is_null(get_post($this->id))){
            throw new Exception('Location ID doesn\'t exist');
        }
        
        if(!isset($this->id)||!$this->id){
            if(isset($this->filemaker_id)){
                $location = get_posts([
                    'post_type'=>'mr_location',
                    'meta_key'=>'filemaker_id',
                    'meta_value'=>$this->filemaker_id,
                    'fields'=>'ids'
                ]);
                if($location){
                    $this->id = $location[0];
                }
                else{
                    $this->id = wp_insert_post([
                        'post_title'=>$this->name,
                        'post_type'=>'mr_location',
                        'meta_input'=>[
                            'filemaker_id'=>$this->filemaker_id
                        ]
                    ]);
                }
            }
            if(isset($this->name)){
                $this->id = wp_insert_post([
                    'post_title'=>$this->name,
                    'post_type'=>'mr_location'
                ]);
            }
            else{
                $this->id = false;
            }
            if(is_wp_error($this->id)||$this->id===0){
                throw new Exception('Couldn\'t insert location');
            }
        }
        if(!isset($this->name)){
            $this->name = get_the_title($this->id);
        }
        return $this->id;
    }

    public function get_full_location(){
        if(!isset($this->id)){
            $this->get_location();
        }
        if(!$this->id){
            return false;
        }
        $filemaker_id = get_post_meta($this->id,'filemaker_id',true);
        return ['id'=>$this->id,'name'=>$this->name,'filemaker_id'=>$filemaker_id];
    }

    public function sync_booking_blocks(){
        try{
            if(isset($this->booking) && is_array($this->booking)){
                //update_field('allow_bookings',true,$this->id);
                if(isset($this->booking['start_date']) && $this->booking['start_date']){
                    $date = date('Ymd',strtotime($this->booking['start_date']));
                    if(!$date){
                        throw new Exception('Invalid start date');
                    }
                    update_field('start_date',$date,$this->id);
                }
                if(isset($this->booking['end_date']) && $this->booking['end_date']){
                    $date = date('Ymd',strtotime($this->booking['end_date']));
                    if(!$date){
                        throw new Exception('Invalid end date');
                    }
                    update_field('end_date',$date,$this->id);
                }
                if(isset($this->booking['lead_time']) && $this->booking['lead_time']){
                    $lead_time = intval($this->booking['lead_time']);
                    if(!$lead_time){
                        throw new Exception('Invalid lead time');
                    }
                    update_field('lead_time',$lead_time,$this->id);
                }
                if(isset($this->booking['booking_slots']) && is_array($this->booking['booking_slots'])){
                    if(get_field('booking_slots',$this->id)){
                        delete_field('booking_slots',$this->id);
                    }
                    foreach($this->booking['booking_slots'] as $slot){
                        $amount = intval($slot['slot_price']);
                        $length = intval($slot['slot_length']);
                        add_row('booking_slots',['slot_price'=>$amount,'slot_length'=>$length],$this->id);
                    }
                }
                if(isset($this->booking['available_times']) && is_array($this->booking['available_times'])){
                    if(get_field('available_times',$this->id)){
                        delete_field('available_times',$this->id);
                    }
                    $days = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday',];
                    foreach($this->booking['available_times'] as $time){
                        $start = date('H:i:s',strtotime($time['opening_time']));
                        $end = date('H:i:s',strtotime($time['closing_time']));
                        $weekday= array_search($time['day'],$days);
                        if(!$start||!$end||!$weekday){
                            throw new Exception('Invalid time for Available Times');
                        }
                        add_row('available_times',['opening_time'=>$start,'closing_time'=>$end,'day'=>$weekday],$this->id);
                    }
                }
                if(isset($this->booking['exceptions'])&&is_array($this->booking['exceptions'])){
                    update_post_meta($this->id,'exceptions',json_encode($this->booking['exceptions']));
                }
                $updated = wp_update_post(['ID'=>$this->id,'post_status'=>'publish']);
                if(is_wp_error($updated)){
                    throw new Exception('Couldn\'t update location');
                }
                $this->status = 'success';
                $this->response = ['id'=>$this->id];
            }
            else{
                throw new Exception('No blocks to sync');
            }
        }
        catch(Exception $e){
            $this->status = 'failed';
            $this->response = $e->getMessage();
        }
    }
    
    public function response(){
        return [$this->status=>$this->response];
    }
}