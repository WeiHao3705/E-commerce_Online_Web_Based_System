<?php

// ------------------------------------------------------
// Voucher Registration DTO
// ------------------------------------------------------
class VoucherRegistrationDTO {
    private $code;
    private $description;
    private $type;
    private $discount_value;
    private $min_spend;
    private $max_discount;
    private $start_date;
    private $end_date;
    private $membership_required;

    public function __construct(
        $code, $description, $type, $discount_value,
        $min_spend, $max_discount, $start_date, $end_date, $membership_required
    ) {
        $this->code = $code;
        $this->description = $description;
        $this->type = $type;
        $this->discount_value = $discount_value;
        $this->min_spend = $min_spend;
        $this->max_discount = $max_discount;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->membership_required = $membership_required;
    }

    //Getters
    public function getCode() { return $this->code; }
    public function getDescription() { return $this->description; }
    public function getType() { return $this->type; }
    public function getDiscountValue() { return $this->discount_value; }
    public function getMinSpend() { return $this->min_spend; }
    public function getMaxDiscount() { return $this->max_discount; }
    public function getStartDate() { return $this->start_date; }
    public function getEndDate() { return $this->end_date; }
    public function getMembershipRequired() { return $this->membership_required; }

    //Setters
    public function setCode($code) { $this->code = $code; }
    public function setDescription($description) { $this->description = $description; }
    public function setType($type) { $this->type = $type; }
    public function setDiscountValue($discount_value) { $this->discount_value = $discount_value; }
    public function setMinSpend($min_spend) { $this->min_spend = $min_spend; }
    public function setMaxDiscount($max_discount) { $this->max_discount = $max_discount; }
    public function setStartDate($start_date) { $this->start_date = $start_date; }
    public function setEndDate($end_date) { $this->end_date = $end_date; }
    public function setMembershipRequired($membership_required) { $this->membership_required = $membership_required; }
}

// ------------------------------------------------------
// Voucher Update DTO
// ------------------------------------------------------
class VoucherUpdateDTO {
    private $voucher_id;
    private $code;
    private $description;
    private $type;
    private $discount_value;
    private $min_spend;
    private $max_discount;
    private $start_date;
    private $end_date;
    private $membership_required;

    public function __construct(
        $voucher_id, $code, $description, $type, $discount_value,
        $min_spend, $max_discount, $start_date, $end_date, $membership_required
    ) {
        $this->voucher_id = $voucher_id;
        $this->code = $code;
        $this->description = $description;
        $this->type = $type;
        $this->discount_value = $discount_value;
        $this->min_spend = $min_spend;
        $this->max_discount = $max_discount;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->membership_required = $membership_required;
    }

    //Getters
    public function getVoucherId() { return $this->voucher_id; }
    public function getCode() { return $this->code; }
    public function getDescription() { return $this->description; }
    public function getType() { return $this->type; }
    public function getDiscountValue() { return $this->discount_value; }
    public function getMinSpend() { return $this->min_spend; }
    public function getMaxDiscount() { return $this->max_discount; }
    public function getStartDate() { return $this->start_date; }
    public function getEndDate() { return $this->end_date; }
    public function getMembershipRequired() { return $this->membership_required; }

    //Setters
    public function setVoucherId($voucher_id) { $this->voucher_id = $voucher_id; }
    public function setCode($code) { $this->code = $code; }
    public function setDescription($description) { $this->description = $description; }
    public function setType($type) { $this->type = $type; }
    public function setDiscountValue($discount_value) { $this->discount_value = $discount_value; }
    public function setMinSpend($min_spend) { $this->min_spend = $min_spend; }
    public function setMaxDiscount($max_discount) { $this->max_discount = $max_discount; }
    public function setStartDate($start_date) { $this->start_date = $start_date; }
    public function setEndDate($end_date) { $this->end_date = $end_date; }
    public function setMembershipRequired($membership_required) { $this->membership_required = $membership_required; }
}

