<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Exception;

/**
 * Description of ValidationException
 *
 * @author gusta
 */
class FormValidationException extends \Exception implements \Throwable {

    /**
     * Erros encontrados na validação
     * @var array
     */
    protected $errors;

    public function __construct(array $errors, string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }

    function getErrors() {
        return $this->errors;
    }

}
