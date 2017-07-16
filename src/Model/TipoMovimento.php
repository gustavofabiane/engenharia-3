<?php

namespace Model;

use RedBeanPHP\R as R;

/**
 * Description of TipoMovimento
 *
 * @author gusta
 */
class TipoMovimento extends AbstractModel implements \ModelInterface\Enumeracao {
    
    
    public static function enumFactory(string $name): \RedBeanPHP\OODBBean {
        
        $findType = R::findOne(
            'tipomovimento',
            ' name = ? ',
            [$name]
        );
        
        if(!$findType) {
            $type = R::dispense('tipomovimento');
            $type->name = $name;
        } else {
            $type = $findType;
        }
        
        return $type;
    }

}
