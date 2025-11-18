<?php

class MemberDTO {
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

    // Getters only (DTO should be read-only)
    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getGender() { return $this->gender; }
    public function getContactNo() { return $this->contact_no; }
    public function getRole() { return $this->role; }
    public function getStatus() { return $this->status; }
}

// ------------------------------------------------------
// Member Registration DTO
// ------------------------------------------------------
class MemberRegistrationDTO {
    private $username;
    private $password;
    private $repeat_password;
    private $full_name;
    private $gender;
    private $contact_no;
    private $email;
    private $security_question;
    private $security_answer;
    private $profile_photo;

    public function __construct(
        $username, $password, $repeat_password,
        $full_name, $gender, $contact_no, $email,
        $security_question, $security_answer, $profile_photo = null
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->repeat_password = $repeat_password;
        $this->full_name = $full_name;
        $this->gender = $gender;
        $this->contact_no = $contact_no;
        $this->email = $email;
        $this->security_question = $security_question;
        $this->security_answer = $security_answer;
        $this->profile_photo = $profile_photo;
    }

    //Getters
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getRepeatPassword() { return $this->repeat_password; }
    public function getFullName() { return $this->full_name; }
    public function getGender() { return $this->gender; }
    public function getContactNo() { return $this->contact_no; }
    public function getEmail() { return $this->email; }
    public function getSecurityQuestion() { return $this->security_question; }
    public function getSecurityAnswer() { return $this->security_answer; }
    public function getProfilePhoto() { return $this->profile_photo; }

    //Setters
    public function setUsername($username) { $this->username = $username; }
    public function setPassword($password) { $this->password = $password; }
    public function setRepeatPassword($repeat_password) { $this->repeat_password = $repeat_password; }
    public function setFullName($full_name) { $this->full_name = $full_name; }
    public function setGender($gender) { $this->gender = $gender; }
    public function setContactNo($contact_no) { $this->contact_no = $contact_no; }
    public function setEmail($email) { $this->email = $email; }
    public function setSecurityQuestion($security_question) { $this->security_question = $security_question; }
    public function setSecurityAnswer($security_answer) { $this->security_answer = $security_answer; }
    public function setProfilePhoto($profile_photo) { $this->profile_photo = $profile_photo; }
}

// ------------------------------------------------------
// Member Update DTO
// ------------------------------------------------------
class MemberUpdateDTO {
    private $user_id;
    private $username;
    private $full_name;
    private $email;
    private $gender;
    private $contact_no;

    public function __construct(
        $user_id, $username, $full_name, $email, $gender, $contact_no
    ) {
        $this->user_id = $user_id;
        $this->username = $username;
        $this->full_name = $full_name;
        $this->email = $email;
        $this->gender = $gender;
        $this->contact_no = $contact_no;
    }

    //Getters
    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getGender() { return $this->gender; }
    public function getContactNo() { return $this->contact_no; }

    //Setters
    public function setUserId($user_id) { $this->user_id = $user_id; }
    public function setUsername($username) { $this->username = $username; }
    public function setFullName($full_name) { $this->full_name = $full_name; }
    public function setEmail($email) { $this->email = $email; }
    public function setGender($gender) { $this->gender = $gender; }
    public function setContactNo($contact_no) { $this->contact_no = $contact_no; }
}

