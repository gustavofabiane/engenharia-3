<?php

namespace Model;

use RedBeanPHP\R as R;

/**
 * Description of StatusDespesa
 *
 * @author gusta
 */
class StatusDespesa extends AbstractModel implements \ModelInterface\Enumeracao {
    
    /**
     * Método factory para geração de objeto StatusDespesa
     * @param string $name
     * @return \RedBeanPHP\OODBBean
     */
    public static function enumFactory(string $name): \RedBeanPHP\OODBBean {
        
        $findStatus = R::findOne(
            'statusdespesa',
            ' name = ? ',
            [$name]
        );
        
        if(!$findStatus) {
            $status = R::dispense('statusdespesa');
            $status->name = $name;
        } else {
            $status = $findStatus;
        }
        
        return $status;
    }

}
