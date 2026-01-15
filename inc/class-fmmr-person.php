<?php 
class MRPerson{
    private $id;
    private $name;

    public function __construct($person){
        if(isset($person['id'])){
            $this->id = intval($person['id']);
        }
        if(isset($person['name'])){
            $this->name = sanitize_text_field($person['name']);
        }
        if(isset($person['filemaker_id'])){
            $this->filemaker_id = intval($person['filemaker_id']);
        }
    }

    public function get_person(){
        if(!isset($this->id)&&!isset($this->name)){
            throw new Exception('Person requires a name or an ID');
        }
        if($this->id && is_null(get_post($this->id))){
            throw new Exception('Person ID doesn\'t exist');
        }
        if(!isset($this->id)||!$this->id){
            if(isset($this->filemaker_id)){
                $person = get_posts([
                    'post_type'=>'mr_people',
                    'meta_key'=>'filemaker_id',
                    'meta_value'=>$this->filemaker_id,
                    'fields'=>'ids'
                ]);
                if($person){
                    $this->id = $person[0];
                }
                else{
                    $person = get_posts([
                        'post_type'=>'mr_people',
                        'title'=>$this->name,
                        'fields'=>'ids'
                    ]);
                    if($person){
                        $this->id = $person[0];
                        update_post_meta($this->id,'filemaker_id',$this->filemaker_id);
                    }
                    else{
                        $this->id = wp_insert_post([
                            'post_title'=>$this->name,
                            'post_type'=>'mr_people',
                            'meta_input'=>[
                                'filemaker_id'=>$this->filemaker_id
                            ]
                        ]);
                    }
                }
            }
            else{
                $person = get_posts([
                    'post_type'=>'mr_people',
                    'title'=>$this->name,
                    'fields'=>'ids'
                ]);
                if($person){
                    $this->id = $person[0];
                }
                else{
                    $this->id = wp_insert_post([
                        'post_title'=>$this->name,
                        'post_type'=>'mr_people',
                    ]);
                }
            }
            if(is_wp_error($this->id)||$this->id===0){
                throw new Exception('Couldn\'t insert person');
            }
        }
        if(!isset($this->name)){
            $this->name = get_the_title($this->id);
        }
        return $this->id;
    }

    public function get_full_person(){
        if(!isset($this->id)){
            $this->get_person();
        }
        $filemaker_id = get_post_meta($this->id,'filemaker_id',true);
        return ['id'=>$this->id,'name'=>$this->name, 'filemaker_id'=>$filemaker_id];
    }
}