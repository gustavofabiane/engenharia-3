<?php

namespace ModelInterface;

/**
 *
 * @author gusta
 */
interface Validavel {
    
    /**
     * Implementa o método de validação de formulário
     * @param array $formData
     */
    public static function isValid(array $formData): array;
    
}
