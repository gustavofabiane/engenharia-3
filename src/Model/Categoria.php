<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Categoria
 *
 * @author gusta
 */
class Categoria extends AbstractModel implements \ModelInterface\Validavel {

    /**
     * Executa quando uma bean é carregada
     */
//    public function open() {
//        $this->ownProdutoList;
//    }

    /**
     * Popula o objeto da Categoria
     * @param \Slim\Http\Request $request requisição com os dados
     * @param \RedBeanPHP\OODBBean $company empresa da categoria
     * @param array $data [opcional]
     * @return \RedBeanPHP\OODBBean
     */
    public function populate(
        \Slim\Http\Request $request, 
        \RedBeanPHP\OODBBean $company = null, 
        array $data = null
    ): \RedBeanPHP\OODBBean {

        $errors = $request->getAttribute('errors', []);

        if (!$data) {
            $data = $request->getParsedBody();
            $this->bean->errors = $errors;
        } else {
            $this->bean->errors = $errors[$data['sequencia']];
        }
        
        if ($company) {
            $this->bean->empresa = $company;
        }

        unset($data['sequencia']);
        unset($data['validacao']);
        unset($data['errors']);
        unset($data['_METHOD']);

        foreach ($data as $property => $value) {
            $this->bean->{$property} = $value;
        }

        return $this->bean;
    }

    /**
     * Gera um lote de categorias
     * @param \Slim\Http\Request $request
     * @param \RedBeanPHP\OODBBean $company
     * @return array
     */
    public static function dispenseBatch(
    \Slim\Http\Request $request, \RedBeanPHP\OODBBean $company
    ): array {

        $bashData = $request->getParsedBody();

        $categories = [];
        foreach ($bashData['categorias'] as $categoria) {
            $categories[] = R::dispense('categoria')->populate($request, $company, $categoria);
        }

        return $categories;
    }

    /**
     * Salva os dados do lote de categorias através de uma transação
     * @param array $categories
     * @return bool
     * @throws \Exception
     */
    public static function storeBatch(array $categories): bool {

        R::begin();

        try {
            foreach ($categories as $category) {
                $checkId = R::store($category);
                if (!is_numeric($checkId) || $checkId == 0) {
                    throw \Exception('Não foi possível adicionar uma categoria do lote');
                }
            }
        } catch (\Exception $ex) {
            R::rollback();
            throw $ex;
        }

        R::commit();

        return true;
    }

    /**
     * Desabilita a categoria e seus produtos
     */
    public function disable() {

        R::begin();

        try {

            $this->bean->desabilitada = true;

            R::store($this->bean);

            $this->disableProducts();
            
        } catch (\Exception $ex) {
            R::rollback();
            throw $ex;
        }

        R::commit();
    }
    
    /**
     * Desabilita os produtos junto da categoria
     */
    protected function disableProducts() {
        
        foreach($this->bean->ownProdutoList as &$product) {
            $product->desabilitado = true;
            $product->desabilita_em = new \DateTime;
        }
        
        unset($product);
    }

    /**
     * Verifica se os dados do formulário de categoria são validos
     * @param array $formData
     * @return array
     */
    public static function isValid(array $formData): array {

        $errors = [];

        if (!V::notEmpty()->regex('/[A-Za-z]{1,100}/')->validate($formData['nome'])) {
            $errors['nome'] = 'Favor informar um nome para a categoria';
        }

        if (!V::notEmpty()->floatVal()->between(0, 100, true)->validate($formData['margemLucro'])) {
            $errors['margemLucro'] = 'Favor informar a margem de lucro corretamente';
        }

        return $errors;
    }

    /**
     * Verifica se um lote de categorias é valido
     * @param array $formData
     * @return array
     */
    public static function isValidBatch(array $formData): array {

        $errors = [];
        
        unset($formData["validacao"]);

        foreach ($formData as $data) {
            $errors[$data['sequencia']] = self::isValid($data);
        }

        return $errors;
    }

}
