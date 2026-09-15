<?php

namespace App\Validators;

abstract class BaseValidator {
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Reset error messages.
     */
    public function clearErrors() {
        $this->errors = [];
    }

    /**
     * Add an error message.
     * 
     * @param string $message
     */
    public function addError($message) {
        $this->errors[] = $message;
    }

    /**
     * Get all accumulated error messages.
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Determine if there are any validation errors.
     * 
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
