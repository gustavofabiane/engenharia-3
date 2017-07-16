<?php

namespace Model;

use RedBeanPHP\R as R;

/**
 * Description of TipoDespesa
 *
 * @author gusta
 */
class TipoDespesa extends AbstractModel implements \ModelInterface\Enumeracao {
    
    /**
     * Método factory para gerar um objeto TipoDespesa
     * @param string $name
     * @return OODBBean
     */
    public static function enumFactory(string $name): \RedBeanPHP\OODBBean {
        
        $findType = R::findOne(
            'tipodespesa',
            ' name = ? ',
            [$name]
        );
        
        if(!$findType) {
            $type = R::dispense('tipodespesa');
            $type->name = $name;
        } else {
            $type = $findType;
        }
        
        return $type;
    }

}
