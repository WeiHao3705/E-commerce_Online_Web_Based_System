<?php

class ActivityLogDTO {
    private $log_id;
    private $admin_id;
    private $action_type;
    private $entity_type;
    private $entity_id;
    private $action_description;
    private $old_values;
    private $new_values;
    private $ip_address;
    private $user_agent;
    private $created_at;

    public function __construct(
        $admin_id,
        $action_type,
        $entity_type,
        $action_description,
        $entity_id = null,
        $old_values = null,
        $new_values = null,
        $ip_address = null,
        $user_agent = null,
        $log_id = null,
        $created_at = null
    ) {
        $this->log_id = $log_id;
        $this->admin_id = $admin_id;
        $this->action_type = $action_type;
        $this->entity_type = $entity_type;
        $this->entity_id = $entity_id;
        $this->action_description = $action_description;
        $this->old_values = $old_values;
        $this->new_values = $new_values;
        $this->ip_address = $ip_address;
        $this->user_agent = $user_agent;
        $this->created_at = $created_at;
    }

    public function getLogId() { return $this->log_id; }
    public function getAdminId() { return $this->admin_id; }
    public function getActionType() { return $this->action_type; }
    public function getEntityType() { return $this->entity_type; }
    public function getEntityId() { return $this->entity_id; }
    public function getActionDescription() { return $this->action_description; }
    public function getOldValues() { return $this->old_values; }
    public function getNewValues() { return $this->new_values; }
    public function getIpAddress() { return $this->ip_address; }
    public function getUserAgent() { return $this->user_agent; }
    public function getCreatedAt() { return $this->created_at; }

    public function setLogId($log_id) { $this->log_id = $log_id; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }
}

