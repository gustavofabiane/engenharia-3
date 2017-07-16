<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Empresa
 *
 * @author gusta
 */
class Empresa extends AbstractModel implements \ModelInterface\Validavel {

    /**
     * Método FUSE para garantir que nao carregue uma empresa inexistente.
     * @throws \RedBeanPHP\RedException
     */
    public function open() {

        if ($this->bean->id == 0) {
            throw new \RedBeanPHP\RedException("Empresa não encontrada");
        }

        $this->bean->estado;
    }

    /**
     * Popula os dados da empresa de acordo com o formulário de cadastro
     * @param \Slim\Http\Request $request
     * @throws \Exception\FormValidationException
     */
    public function populate(\Slim\Http\Request $request) {
        $properties = parent::populate($request);

        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'saldoInicial':
//                    $saldo = str_replace($value, '.', '');
//                    $saldo = str_replace($value, ',', '.');
                    $this->bean->saldoInicial = floatval($value);
                    break;
                case 'estado':
                    $this->bean->estado = R::load('estado', $value);
                    break;
                default:
                    $this->bean->{$property} = $value;
            }
        }
        
        if(!empty($this->bean->errors)) {
            throw new \Exception\FormValidationException($this->bean->errors, 'Dados do formulário inválidos');
        } else {
            unset($this->bean->errors);
        }
    }

    /**
     * Retorna os produtos da empresa disponíveis para venda<br>
     * Leva em conta o estoque do produto.
     * @return array OODBean - Produtos com estoque e disponíveis para venda.
     */
    public function productsForSale() {

        $products = R::findMulti('produto', "
            SELECT
                produto.*     
            FROM
                produto 
                LEFT JOIN estoque a ON (produto.id = a.produto_id AND a.direcao = 'S')
                LEFT JOIN estoque b ON (produto.id = b.produto_id AND b.direcao = 'E')
            WHERE
                empresa_id = ?
            GROUP BY 
                produto.id
            HAVING
                NVL(SUM(b.quantidade), 0) > NVL(SUM(a.quantidade), 0)
                ", [$this->bean->id]);

        return $products["produto"];
    }

    /**
     * Atualiza os metodos de pagamento aceitos pela empresa
     * @param \Slim\Http\Request $request
     * @param \RedBeanPHP\OODBBean $user
     */
    public function updatePaymentMethods(\Slim\Http\Request $request, \RedBeanPHP\OODBBean $user) {

        $this->bean->ownEmpresaPagamentoList = [];

        $requestData = $request->getParsedBody();

        $methods = $requestData["metodo"];

        foreach ($methods as $methodId) {
            $method = R::load('pagamento', $methodId);
            $this->bean->link('empresa_pagamento', [
                        'responsavel' => $user
                    ])->pagamento = $method;
        }

        R::store($this->bean);
    }

    /**
     * Valida os dados da Empresa
     * @param bool $validation <TRUE> se for validação de formulário
     * @throws \Exception\FormValidationException
     */
    public function update() {

        if ($this->bean->fechada) {
            throw new \Exception("Ação não permitida. Empresa fechada.");
        }

        parent::update();
    }

    /**
     * Gera um movimento a partir do saldo inicial da empresa<br>
     * Inicia o fluxo de caixa da empresa
     * @param float $cash
     * @return Model\Movimento movimento gerado
     */
    public function generateInitialCash(float $cash) {

        $movement = R::dispense('movimento');

        $movement->tipoMovimento = TipoMovimento::enumFactory('Saldo Inicial');
        $movement->data = new \DateTime;
        $movement->direcao = Movimento::ENTRADA;
        $movement->valor = $cash;
        $movement->empresa = $this->bean;

        return $movement;
    }

    /**
     * Valida os dados da empresa
     * @param array $formData
     */
    public static function isValid(array $formData): array {

        //TODO: Reformular a validação de empresa
        $errors = self::isValidAddress($formData);

        if (!V::cnpj()->validate($formData["cnpj"])) {
            $errors['cnpj'] = 'Favor informar um CNPJ válido para a empresa.';
        }

        if (!V::notEmpty()->validate($formData["razaoSocial"])) {
            $errors['razaoSocial'] = 'Favor informar a razão social da empresa.';
        }

        if (!V::alnum()->notEmpty()->validate($formData["fantasia"])) {
            $errors['fantasia'] = 'Favor informar o nome fantasia da empresa.';
        }

        if (V::notEmpty()->validate($formData["telefone"])) {
            $phone = str_replace(['(', ')', '-', ' '], '', $formData["telefone"]);
            if (!V::digit()->validate($phone)) {
                $errors['telefone'] = 'Favor informar um número de telefone válido.';
            }
        } else {
            unset($formData["telefone"]);
        }

        if (!V::email()->validate($formData["email"])) {
            $errors['email'] = 'Favor informar um e-mail válido.';
        }

        return $errors;
    }

}
