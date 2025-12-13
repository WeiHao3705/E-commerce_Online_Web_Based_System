<?php

class AdminDTO {
    private $user_id;
    private $username;
    private $full_name;
    private $email;
    private $gender;
    private $contact_no;
    private $role;
    private $status;

    public function __construct($user_id, $username, $full_name, $email, $gender, $contact_no, $role, $status) {
        $this->user_id = $user_id;
        $this->username = $username;
        $this->full_name = $full_name;
        $this->email = $email;
        $this->gender = $gender;
        $this->contact_no = $contact_no;
        $this->role = $role;
        $this->status = $status;
    }

    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getGender() { return $this->gender; }
    public function getContactNo() { return $this->contact_no; }
    public function getRole() { return $this->role; }
    public function getStatus() { return $this->status; }
}

class AdminRegistrationDTO {
    private $username;
    private $password;
    private $repeat_password;
    private $full_name;
    private $gender;
    private $contact_no;
    private $email;
    private $profile_photo;

    public function __construct(
        $username,
        $password,
        $repeat_password,
        $full_name,
        $gender,
        $contact_no,
        $email,
        $profile_photo = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->repeat_password = $repeat_password;
        $this->full_name = $full_name;
        $this->gender = $gender;
        $this->contact_no = $contact_no;
        $this->email = $email;
        $this->profile_photo = $profile_photo;
    }

    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getRepeatPassword() { return $this->repeat_password; }
    public function getFullName() { return $this->full_name; }
    public function getGender() { return $this->gender; }
    public function getContactNo() { return $this->contact_no; }
    public function getEmail() { return $this->email; }
    public function getProfilePhoto() { return $this->profile_photo; }

    public function setPassword($password) { $this->password = $password; }
    public function setRepeatPassword($repeat_password) { $this->repeat_password = $repeat_password; }
    public function setProfilePhoto($profile_photo) { $this->profile_photo = $profile_photo; }
}

class AdminUpdateDTO {
    private $user_id;
    private $username;
    private $full_name;
    private $email;
    private $gender;
    private $contact_no;

    public function __construct($user_id, $username, $full_name, $email, $gender, $contact_no) {
        $this->user_id = $user_id;
        $this->username = $username;
        $this->full_name = $full_name;
        $this->email = $email;
        $this->gender = $gender;
        $this->contact_no = $contact_no;
    }

    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getGender() { return $this->gender; }
    public function getContactNo() { return $this->contact_no; }
}
