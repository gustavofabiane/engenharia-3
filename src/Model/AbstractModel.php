<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Model;

use Respect\Validation\Validator as V;

/**
 * Description of AbstractModel
 *
 * @author gusta
 */
abstract class AbstractModel extends \RedBeanPHP\SimpleModel {

    /**
     * Método abstrato do update()
     * <br>Executa quando o RedBeanPHP\R::store() é chamado
     * @throws \Exception\FormValidationException
     */
    public function update() {
        
        if(!empty($this->bean->errors)) {
            throw new \Exception\FormValidationException($this->bean->errors, 'Dados do formulário inválidos');
        } else {
            unset($this->bean->errors);
        }

        if (!$this->bean->id) {
            $this->bean->criadoEm = new \DateTime;
        } else {
            $this->bean->alteradoEm = new \DateTime;
        }
    }

    /**
     * Inicializa o carregamento do objeto a partir dos dados da requisição
     * @param \Slim\Http\Request $request
     * @return array
     */
    public function populate(\Slim\Http\Request $request) {
        
        $this->bean->errors = $request->getAttribute('errors', []);

        $properties = $request->getParsedBody();
        
        unset($properties["_METHOD"]);
        unset($properties["validacao"]);
        unset($properties["errors"]);
        
        return $properties;
    }

    protected static function isValidAddress(array $formData): array {

        $errors = [];

        if (!V::notEmpty()->validate($formData['endereco'])) {
            $errors['endereco'] = 'Favor informar seu endereço ("Rua Exemplo, 000")';
        }

        if (!V::notEmpty()->validate($formData['bairro'])) {
            $errors['bairro'] = 'Favor informar sue bairro.';
        }

        if (!V::notEmpty()->validate($formData['cidade'])) {
            $errors['cidade'] = 'Favor informar sua cidade.';
        }

        if (!V::intVal()->between(0, 28)->validate((int) $formData['estado'])) {
            $errors['estado'] = 'Favor informar seu estado.';
        }

        return $errors;
    }

}
