<?php
class Activity {
  
  private $display_name = '<em>Anon</em>';
  private $image = null;
  private $actor_id = 0;
  private $session_id = null;
  private $action_text = null;
  private $date = null;
  private $id;
  private $type;
  
  public function __construct($activity_type, $action_text, $options = array()) {
    
    $options = $this->set_default_options($options);
    
    $this->type = $activity_type;
    $this->id = $options['messageTime'];
    $this->date = gmdate('Y-m-d\TH:i:s\Z');
    
    $this->action_text = $action_text;
    $this->display_name = $options['displayName'];
    $this->image = $options['image'];
    $this->actor_id = $options['actorId'];
    $this->session_id = $options['sessionId'];
    
  }
  
  public function getMessage() {
    $activity = array(
      'id' => $this->id,
      'message_time' => $this->id,
      'body' => $this->action_text,
      'published' => $this->date,
      'type' => $this->type,
      'actor' => array(
        'displayName' => $this->display_name,
        'objectType' => 'person',
        'id' => $this->actor_id,
        'image' => $this->image,
        'sessionId' => $this->session_id
      )
    );
    return $activity;
  }
  
  private function set_default_options($options) {
    $defaults = array ( 'displayName' => null,
                        'image' => null,
                        'actorId' => 0,
                        'messageTime' => gmdate('Y-m-d H:i:s'),
                        'sessionId' => null,
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
