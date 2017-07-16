<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Fornecedor
 *
 * @author gusta
 */
class Fornecedor extends AbstractModel implements \ModelInterface\Validavel {

    /**
     * Popula o fornecedor
     * @param \Slim\Http\Request $request
     */
    public function populate(\Slim\Http\Request $request, \RedBeanPHP\OODBBean $company = null) {

        $properties = parent::populate($request);

        if ($company) {
            $this->bean->empresa = $company;
        }

        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'telefone':
                    $this->bean->telefone = str_replace(['(', ')', '-', ' '], '', $value);
                    break;
                default:
                    $this->bean->{$property} = $value;
            }
        }
    }

    /**
     * Desabilita o fornecedor
     */
    public function disable() {
        $this->bean->desabilitado = true;
        R::store($this->bean);
    }

    /**
     * Valida os dados do fornecedor
     * @param array $formData
     * @param \RedBeanPHP\OODBBean $company
     * @return array
     */
    public static function isValid(array $formData, \RedBeanPHP\OODBBean $company = null): array {

        $errors = [];

        if (!V::notEmpty()->validate($formData['razaoSocial'])) {
            $errors['razaoSocial'] = 'Favor informar a razão social do fornecedor';
        }

        switch ((int) $formData['tipoPessoa']) {
            case 1:
                if (!V::cpf()->validate($formData['cgcCpf'])) {
                    $errors['cgcCpf'] = 'Favor informar um CPF válido';
                }
                break;
            case 2:
                if (!V::cnpj()->validate($formData['cgcCpf'])) {
                    $errors['cgcCpf'] = 'Favor informar um CNPJ válido';
                }
                break;
            default:
                $errors['tipoPessoa'] = 'Favor informar se o fornecedor é Pessoa Jurídica ou Física';
        }

        if (!V::email()->validate($formData['email'])) {
            $errors['email'] = 'Favor informar um e-mail válido';
        }

        $phone = str_replace(['(', ')', '-', ' '], '', $formData['telefone']);
        if (!V::notEmpty()->digit()->validate($phone)) {
            $errors['telefone'] = 'Favor informar um número de telefone para contato';
        }

        return $errors;
    }

    public static function isValidNew(array $formData, \RedBeanPHP\OODBBean $company = null): array {
        
        $errors = self::isValid($formData, $company);
        
        if (R::count('fornecedor', 
            ' tipo_pessoa = ? AND cgc_cpf = ? AND empresa_id = ? ', 
            [$formData['tipoPessoa'], $formData['cgcCpf'], $company->id])
        ) {
            $errors['cgcCpf'] = 'Este número de cadastro já está registrado!';
        }
        
        return $errors;
    }

}
