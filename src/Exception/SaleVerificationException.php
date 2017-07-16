<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Exception;

/**
 * Description of SaleVerificationException
 *
 * @author gusta
 */
class SaleVerificationException extends FormValidationException {

    /**
     * Dados da venda com erro
     * @var array
     */
    private $saleData;

    /**
     * Lança uma Excessão de Verificação de Venda
     * @param array $saleData
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(\RedBeanPHP\OODBBean $saleData = null, array $errors = [], string $message = "", int $code = 0, \Throwable $previous = null) {
        parent::__construct($errors, $message, $code, $previous);
        $this->saleData = $saleData;
    }

    /**
     * Get Sale Data
     * @return array
     */
    public function getSaleData() {
        return $this->saleData;
    }

}
