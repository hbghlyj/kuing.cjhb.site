<?php
class Activity {
  
  private $display_name = '<em>Anon</em>';
  private $image = null;
  private $action_text = null;
  private $date = null;
  private $id;
  private $type;
  
  public function __construct($activity_type, $action_text, $options = array()) {
    
    $options = $this->set_default_options($options);
      
    date_default_timezone_set('UTC');
    
    $this->type = $activity_type;
    $this->id = uniqid();
    $this->date = date('r');
    
    $this->action_text = $action_text;
    $this->display_name = $options['displayName'];
    $this->image = $options['image'];
    
  }
  
  public function getMessage() {
    $activity = array(
      'id' => $this->id,
      'body' => $this->action_text,
      'published' => $this->date,
      'type' => $this->type,
      'actor' => array(
        'displayName' => $this->display_name,
        'objectType' => 'person',
        'image' => $this->image
      )
    );
    return $activity;
  }
  
  private function set_default_options($options) {
    $defaults = array ( 'displayName' => null,
                        'image' => '/data/avatar/noavatar.svg',
                      );
    foreach ($defaults as $key => $value) {
      if(!isset($options[$key])) {
        $options[$key] = $value;
      }
    }
    
    return $options;
  }
}
?>