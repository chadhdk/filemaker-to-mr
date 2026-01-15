<?php

class MREvent{
    private array $params;
    private int $id;
    private int $filemaker_id;
    private string $type;
    private string $title;
    private $name;
    private array $instructors=[];
    private array $substitutes=[];
    private $location;
    private $fee;
    private bool $internal;
    private string $url;
    private int $capacity;
    private bool $waiting_list;
    private bool $card_eligible;
    private $dates;
    private $description;
    private $accessibility_notes;
    private $additional_notes;
    private $generic_date;
    private $generic_time;
    private $dropin_register;
    private $class_type;
    private $workshop_type;
    private $event_type;
    private $event_series;
    private $virtual;
    private $virtual_url;
    private $virtual_password;
    private $single_day_drop_ins;
    private $fee_single;
    private $substitutes_raw;
    private $notes;
    public $status;
    public $response;

    public function __construct($params){
        if(!$params){
            $this->status = 'failed';
            $this->response = 'No parameters provided to the API';
        }
        else{
            $this->params = $params;
            $this->params_to_vars();
            $this->create_or_update();
        }
    }

    public function params_to_vars(){
        if(isset($this->params['id']) && is_int($this->params['id'])){
            $this->id=$this->params['id'];
        }
        else{
            $this->id=0;
        }
        if(isset($this->params['filemaker_id']) && is_int($this->params['filemaker_id'])){
            $this->filemaker_id=$this->params['filemaker_id'];
        }
        if(isset($this->params['event_type']) && is_string($this->params['event_type'])){
            $this->type='mr_'.sanitize_text_field($this->params['event_type']);
        }
        elseif(isset($this->params['type']) && is_string($this->params['type'])){
            $this->type='mr_'.sanitize_text_field($this->params['type']);
        }
        if(isset($this->params['name']) && is_string($this->params['name'])){
            $this->name=sanitize_text_field($this->params['name']);
        }
        if(isset($this->params['internal']) && is_bool($this->params['internal'])){
            $this->internal=$this->params['internal'];
        }
        else{
            $this->internal = true;
        }
        if(isset($this->params['waiting_list']) && is_bool($this->params['waiting_list'])){
            $this->waiting_list=$this->params['waiting_list'];
        }
        if(isset($this->params['card_eligible']) && is_bool($this->params['card_eligible'])){
            $this->card_eligible=$this->params['card_eligible'];
        }
        if(isset($this->params['url']) && is_string($this->params['url'])){
            $this->url=sanitize_url($this->params['url']);
        }
        if(isset($this->params['presentation']) && is_string($this->params['presentation'])){
            $this->virtual=sanitize_text_field($this->params['presentation']);
            if($this->virtual == 'in_person'){
                $this->virtual = 'inperson';
            }
        }
        else{
            $this->virtual = "inperson";
        }
        if(isset($this->params['virtual_url']) && is_string($this->params['virtual_url'])){
            $this->virtual_url=sanitize_url($this->params['virtual_url']);
        }
        if(isset($this->params['virtual_password']) && is_string($this->params['virtual_password'])){
            $this->virtual_password=$this->params['virtual_password'];
        }
        if(isset($this->params['capacity']) && is_int($this->params['capacity'])){
            $this->capacity=$this->params['capacity'];
        }
        if(isset($this->params['dropin_register']) && is_string($this->params['dropin_register'])){
            $this->dropin_register=$this->params['dropin_register'];
        }
        if(isset($this->params['teacher'])&&is_array($this->params['teacher'])){
            foreach($this->params['teacher'] as $instructor){
                $this->instructors[] = new MRPerson($instructor);
            }
        }
        if(isset($this->params['substitutes'])&&is_array($this->params['substitutes'])){
            foreach($this->params['substitutes'] as $substitute){
                $this->substitutes[] = new MRPerson($substitute);
                $this->substitutes_raw[] = $substitute;
            }
        }
        $this->location=isset($this->params['location'])?new MRLocation($this->params['location']):null;
        $this->fee=isset($this->params['fee'])?new MRFee($this->params['fee']):null;
        $this->dates=isset($this->params['instances'])&&is_array($this->params['instances'])?new MRDates($this->params['instances']):null;
        $this->class_type = isset($this->params['class_type'])?$this->params['class_type']:null;
        $this->event_series = isset($this->params['event_series'])?$this->params['event_series']:null;
        $this->workshop_type = isset($this->params['workshop_type'])?$this->params['workshop_type']:null;
        $this->generic_date = isset($this->params['generic_date'])?$this->params['generic_date']:null;
        $this->generic_time = isset($this->params['generic_time'])?$this->params['generic_time']:null;
        $this->description = isset($this->params['description'])?$this->params['description']:null;
        $this->accessibility_notes = isset($this->params['accessibility_notes'])?$this->params['accessibility_notes']:null;
        $this->additional_notes = isset($this->params['additional_notes'])?$this->params['additional_notes']:null;
        $this->single_day_drop_ins = isset($this->params['allow_single_day_drop-ins'])?$this->params['allow_single_day_drop-ins']:null;
        $this->fee_single = isset($this->params['fee_single'])?new MRFee($this->params['fee_single'],'_single'):null;
    }

    public function create_or_update(){
        try{
            $meta_input = [];
            $dates = null;
            $dates_acf=null;
            $fee = null;
            $fee_single = null;
            if(!isset($this->id)&&isset($this->filemaker_id)){
                $event = get_posts([
                    'post_type'=>$this->type,
                    'post_status'=>['private','publish','draft'],
                    'meta_key'=>'filemaker_id',
                    'meta_value'=>$this->filemaker_id,
                    'fields'=>'ids'
                ]);
                if($event){
                    $this->id = $event[0];
                }
            }
            if($this->dates){
                $dates = $this->dates->get_dates();
            }
            if($this->fee){
                $fee = $this->fee->get_fee();
            }
            if($dates){
                if(isset($dates['dates'])){
                    $dates_acf = array_pop($dates);
                }
                $meta_input = array_merge($meta_input,$dates);
            }
            if($fee){
                $meta_input = array_merge($meta_input,$fee);
            }
            if($this->fee_single){
                $fee_single = $this->fee_single->get_fee();
            }
            if($fee_single){
                $meta_input = array_merge($meta_input,$fee_single);
            }
            if(isset($this->filemaker_id)){
                $meta_input['filemaker_id']=$this->filemaker_id;
            }
            if(isset($this->internal)){
                $meta_input['details_ticketing_state'] = $this->internal?'internal':'external';
            }
            if(isset($this->card_eligible)){
                $meta_input['details_card_eligible'] = $this->card_eligible?'yes':'no';
            }
            if(isset($this->waiting_list)){
                $meta_input['details_enable_waiting_list'] = $this->waiting_list?'1':'0';
            }
            if(isset($this->url)){
                $meta_input['details_external_ticket_url']=$this->url;
            }
            if(isset($this->virtual)){
                $meta_input['details_virtual_or_in-person']=$this->virtual;
            }
            if(isset($this->virtual_url)){
                $meta_input['details_virtual_event_link']=$this->virtual_url;
            }
            if(isset($this->virtual_password)){
                $meta_input['details_virtual_link_password']=$this->virtual_password;
            }
            if(isset($this->capacity)){
                $meta_input['details_capacity']=$this->capacity;
            }
            if(isset($this->instructors)&&$this->instructors){
                $instructors = array_map(function($n){return $n->get_person();},$this->instructors);
                $meta_input['details_teacher_instructor']=$instructors;
            }
            if(isset($this->substitutes) && $this->substitutes){
                $substitutes = array_map(function($n){return $n->get_person();},$this->substitutes);
                $meta_input['details_substitute_instructor']=$substitutes;
            }
            if(isset($this->location)){
                $meta_input['details_location']=$this->location->get_location();
            }
            if(isset($this->dropin_register)){
                $meta_input['details_dropin_register']=$this->dropin_register;
            }
            if(isset($this->single_day_drop_ins)){
                $meta_input['details_allow_single_day_drop-ins']=$this->single_day_drop_ins;
            }
            if($this->type==="mr_event"){
                $meta_input['details_faculty_or_artists']='Artists';
            }
            else{
                $meta_input['details_faculty_or_artists']='Faculty';
            }
            $substitute_str = '';
            if(isset($this->substitutes_raw)&&$this->substitutes_raw){
                $substitute_str.='<ul>';
                uasort($this->substitutes_raw,function($a,$b){
                    return strtotime($a['date'])>strtotime($b['date']);
                });
                foreach($this->substitutes_raw as $substitute){
                    $user_post = get_posts(['title'=>$substitute['name'],'post_type'=>'mr_people','posts_per_page'=>1]);
                    if(isset($user_post[0])){
                        $name = '<a href="'.get_the_permalink($user_post[0]->ID).'">'.$substitute['name'].'</a>';
                    }
                    else{
                        $name = $substitute['name'];
                    }
                    $substitute_str.='<li>'.date('F jS, Y',strtotime($substitute['date'])).' sub: '.$name.'</li>';
                }
                $substitute_str.='</ul>';
            }
            $content_block_count = 0;
            if((isset($this->description)||$substitute_str)&&(!isset($this->id)||$this->id===0)){
                $str = $substitute_str.(isset($this->description)?$this->description:'');
                $meta_input['content_blocks_0_text']=$str;
                $meta_input['_content_blocks_0_text']='field_62542f8e8798c';
                $meta_input['content_blocks']=['intro_text'];
                $meta_input['_content_blocks']='field_62332bee39c6e';
                $content_block_count++;
            }
            if($this->accessibility_notes&&(!isset($this->id)||$this->id===0)){
                $meta_input['content_blocks_'.$content_block_count.'_sub_heading']='Accessibility Notes';
                $meta_input['_content_blocks_'.$content_block_count.'_sub_heading']='field_623344515554a';
                $meta_input['content_blocks_'.$content_block_count.'_heading_level']='h3';
                $meta_input['_content_blocks_'.$content_block_count.'_heading_level']='field_62bdca00f6515';
                $meta_input['content_blocks_'.($content_block_count+1).'_text']=$this->accessibility_notes.($this->additional_notes?:'');
                $meta_input['_content_blocks_'.($content_block_count+1).'_text']='field_62332c0239c6f';
                $meta_input['content_blocks'][]='sub_heading';
                $meta_input['content_blocks'][]='regular_text';
                if(!isset($meta_input['_content_blocks'])){
                    $meta_input['_content_blocks']='field_62332bee39c6e';
                }
            }
            if(isset($this->generic_date)){
                $meta_input['generic_dates_times']=1;
                $meta_input['_generic_dates_times']='field_629f2f3ac9803';
                $meta_input['generic_dates_times_0_dates']=$this->generic_date;
                $meta_input['_generic_dates_times_0_dates']='field_629f2f3ac9804';
                $meta_input['generic_dates_times_0_times'] = isset($this->generic_time)?$this->generic_time:'';
                $meta_input['_generic_dates_times_0_times'] = 'field_629f2f3ac9805';
            }
            
            if(isset($this->type) && $this->type == 'mr_workshop'){
                $meta_input['multiday_event']=1;
            }
            $this->response = json_encode($meta_input);
            $args = [];
            if(isset($this->id)&&$this->id){
                $args['ID']=$this->id;
                $args['post_title']=get_the_title($this->id);
            }
            else{
                $this->id == 0;
            }
            if(isset($this->name)){
                $args['post_title']=$this->name;
            }
            if(isset($this->type)){
                $args['post_type']=$this->type;
            }
            if(isset($this->filemaker_id)&&!$this->id){
                $args['post_name']=$this->filemaker_id;
            }
            if($meta_input){
                $args['meta_input']=$meta_input;
            }
            $args['post_content']='';
            if(isset($this->id)&&$this->id){
                $this->id = wp_update_post($args);
            }
            else{
                $this->id = wp_insert_post($args);
            }
            if($this->id==0||is_wp_error($this->id)){
                $this->status = 'failed';
                $this->response = 'Could not insert post';
            }
            else{
                if($dates_acf){
                    $count_rows = 0;
                    $dates_rows = get_field('all_dates',$this->id);
                    if($dates_rows){
                        $count_rows = count($dates_rows);
                    }
                    foreach($dates_acf as $i=>$date){
                        if($i<$count_rows){
                            update_row('all_dates',$i+1,$date,$this->id);
                        }
                        else{
                            add_row('all_dates',$date,$this->id);
                        }
                    }
                    $i++;
                    while($count_rows>$i){
                        delete_row('all_dates',$count_rows,$this->id);
                        $count_rows--;
                    }
                }
                if(isset($this->class_type)){
                    $slug = sanitize_title($this->class_type);
                    if(term_exists($slug,'mr_class_type')){
                        $term = get_term_by('slug',$slug,'mr_class_type');
                        $term_id = $term->term_id;
                    }
                    else{
                        $term = wp_insert_term($this->class_type,'mr_class_type',['slug'=>$slug]);
                        if(!is_wp_error($term)){
                            $term_id = $term['term_id'];
                        }
                    }
                    wp_set_post_terms($this->id,[$term_id],'mr_class_type',true);
                }
                if(isset($this->workshop_type)&&$this->workshop_type){
                    $slug = sanitize_title($this->workshop_type);
                    if(term_exists($slug,'mr_workshop_type')){
                        $term = get_term_by('slug',$slug,'mr_workshop_type');
                        $term_id = $term->term_id;
                    }
                    else{
                        $term = wp_insert_term($this->workshop_type,'mr_workshop_type',['slug'=>$slug]);
                        if(!is_wp_error($term)){
                            $term_id = $term['term_id'];
                        }
                    }
                    wp_set_post_terms($this->id,[$term_id],'mr_workshop_type',true);
                }
                if(isset($this->event_series)&&$this->event_series){
                    $slug = sanitize_title($this->event_series);
                    if(term_exists($slug,$this->event_series)){
                        $term = get_term_by('slug',$slug,'mr_event_type');
                        $term_id = $term->term_id;
                    }
                    else{
                        $term = wp_insert_term($this->event_series,'mr_event_type',['slug'=>$slug]);
                        if(!is_wp_error($term)){
                            $term_id = $term['term_id'];
                        }
                    }
                    wp_set_post_terms($this->id,[$term_id],'mr_event_type',true);
                }
                $this->status = 'success';
                $response = ['id'=>$this->id];
                $people = [];
                if(isset($this->instructors)){
                    $instructors = array_map(function($n){return $n->get_full_person();},$this->instructors);
                    $people = array_merge($people,$instructors);
                }
                if(isset($this->substitutes)){
                    $substitutes = array_map(function($n){return $n->get_full_person();},$this->substitutes);
                    $people = array_merge($people,$substitutes);
                }
                $response['people']=$people;
                if(isset($this->location)){
                    $response['location']=$this->location->get_full_location();
                }
                $response['filemaker_id']=get_post_meta($this->id,'filemaker_id',true);
                $this->response = $response;
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
/*
ID int
        Type {event,class,workshop,melt}
        Title str
        Dates arr [{date:YMD,start:H:i:s,end:H:i:s}]
        Instructor array {id,name}
        location array {id,name}
        Fee object type {free,fixed_fee, sliding_scale, donation, fee plus scale}
                    fee
                    min
                    max
                    step
        Internal Ticket bool
        External URL 
        Virtual virtual,inperson,hybrid
        Virtual Link
        Virtual Password
        Capacity
        Enable Waiting List
        Card Eligible*/