<?php

namespace ModelInterface;

/**
 *
 * @author gusta
 */
interface Enumeracao {
    
    public static function enumFactory(string $name): \RedBeanPHP\OODBBean; 
    
}
