<?php

namespace Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Description of FormValidation
 *
 * @author gusta
 */
class FormValidation extends AbstractMiddleware {

    /**
     * Middleware de validação de formulário
     * @param Request $request
     * @param Response $response
     * @param \Middleware\callable $next
     */
    public function __invoke(Request $request, Response $response, callable $next): Response {

        $body = $request->getParsedBody();

        if (!$request->isGet() && isset($body["validacao"])) {

            $this->container['setupRedBean'];

            $validacao = $this->{$body['validacao']}($body);

            $request = $request->withAttribute('errors', $validacao);
        }

        return $next($request, $response);
    }

    /**
     * Executa a validação para atualização de Empresa
     * @param array $formData
     * @return array
     */
    protected function company(array $formData): array {
        return \Model\Empresa::isValid($formData);
    }

    /**
     * Executa a validação para novo Usuário
     * @param array $formData
     * @return array
     */
    protected function newUser(array $formData): array {
        return \Model\Usuario::isValidNew($formData);
    }

    /**
     * Executa a validação para novo Usuário
     * @param array $formData
     * @return array
     */
    protected function newAccount(array $formData): array {
        return \Model\Usuario::isValidNewAccount($formData);
    }

    /**
     * Executa a validação para alteração de Usuário
     * @param array $formData
     * @return array
     */
    protected function updateUser(array $formData): array {
        return \Model\Usuario::isValidUpdate($formData);
    }

    /**
     * Executa a validação para formulário de categorias em lote
     * @param array $formData
     * @return array
     */
    protected function categoryBatch(array $formData): array {
        return \Model\Categoria::isValidBatch($formData);
    }

    /**
     * Executa validação para atualização de categoria
     * @param array $formData
     * @return array
     */
    protected function updateCategory(array $formData): array {
        return \Model\Categoria::isValid($formData);
    }

    /**
     * Executa validação para novo fornecedor
     * @param array $formData
     * @return array
     */
    protected function newSupplier(array $formData): array {
        $company = $this->container['session']->get('company');
        return \Model\Fornecedor::isValidNew($formData, $company);
    }

    /**
     * Executa validação para fornecedor
     * @param array $formData
     * @return array
     */
    protected function supplier(array $formData): array {
        return \Model\Fornecedor::isValid($formData);
    }

    /**
     * Executa a validação para o produto
     * @param array $formData
     * @return array
     */
    protected function bill(array $formData): array {
        return \Model\Despesa::isValid($formData);
    }
    
    /**
     * 
     * @param array $formData
     * @return array
     */
    protected function product(array $formData): array {
        return \Model\Produto::isValid($formData);
    }

}
