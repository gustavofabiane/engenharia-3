<?php

namespace Integration\Twig;

/**
 * Description of RedBeanRelationshipTwigAdapter
 *
 * @author gusta
 */
class RedBeanRelationshipTwigAdapter extends \Twig_Extension {

    public function getFunctions() {
        return [
            new \Twig_Function('ownList', [$this, 'ownList']),
            new \Twig_Function('sharedList', [$this, 'sharedList']),
            new \Twig_Function('relationship', [$this, 'relationship'])
        ];
    }

    public function ownList(\RedBeanPHP\OODBBean $bean = null, string $list = null) {

        if (is_null($bean)) {
            return null;
        }

        $ownList = 'own' . ucfirst($list) . 'List';

        return $bean->{$ownList};
    }

    public function sharedList(\RedBeanPHP\OODBBean $bean = null, string $list = null) {

        if (is_null($bean)) {
            return null;
        }

        $sharedList = 'shared' . ucfirst($list) . 'List';

        return $bean->{$sharedList};
    }

    public function relationship(\RedBeanPHP\OODBBean $bean = null, string $property = null) {

        if (is_null($bean)) {
            return null;
        }

        return $bean->{$property};
    }

}
