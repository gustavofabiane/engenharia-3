<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Supplier
 *
 * @author gusta
 */
class Supplier extends AbstractController {
    
    /**
     * Lista os fornecedores da empresa
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function companySuppliers(Request $request, Response $response): Response {
        
        $this->responseData['messages'] = $request->getAttribute('messages', []);
        
        $this->responseData['suppliers'] = R::findAll(
            'fornecedor', 
            ' empresa_id = ? AND desabilitado IS NULL ORDER BY criado_em DESC', 
            [$this->company->id]
        );
        
        return $this->container['view']->render($response, 'supplier/list.twig', $this->responseData);
    }
    
    /**
     * Cadastra um novo fornecedor
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function newSupplier(Request $request, Response $response): Response {
        
        if($request->isPost()) {
            
            $supplier = R::dispense('fornecedor');
            $supplier->populate($request, $this->company);
            
            try {
                
                R::store($supplier);
                
                $this->container['flash']->addMessage('success', 'Novo fornecedor cadastrado');
                
                return $response->withRedirect('/fornecedores/', 302);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['message'] = $ex->getMessage();
                $this->responseData['supplier'] = $supplier;
            }
        }
        
        return $this->container['view']->render($response, 'supplier/new.twig', $this->responseData);
    }
    /**
     * Solicita a alteração dos dados de um fornecedor
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function updateSupplier(Request $request, Response $response, array $arguments): Response {
        
        $supplier = R::load('fornecedor', $arguments['id']);
        
        if($request->isPut()) {
            
            $supplier->populate($request);
            
            try {
                
                R::store($supplier);
                
                $this->container['flash']->addMessage('success', 'Fornecedor atualizado com sucesso');
                
                return $response->withRedirect('/fornecedores/', 302);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['message'] = $ex->getMessage();
            }
        }
        
        $this->responseData['supplier'] = $supplier;
        
        return $this->container['view']->render($response, '/supplier/update.twig', $this->responseData);
    }
    /**
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function disableSupplier(Request $request, Response $response, array $arguments): Response {
        
        $supplier = R::load('fornecedor', $arguments['id']);
        
        if($request->isDelete()) {
            
            $supplier->disable();
            
            $this->container['flash']->addMessage(
                'success', 
                'Fornecedor desabilitado com sucesso'
            );
            
            return $response->withRedirect('/fornecedores/', 302);
            
        }
        
        $this->responseData['supplier'] = $supplier;
        
        return $this->container['view']->render($response, 'supplier/disable.twig', $this->responseData);
    }
    
    /**
     * Carrega os fornecedores do produto em uma lista de opções para o campo select na tela de recebimento de produtos
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     */
    public function productReceiptSuppliers(Request $request, Response $response, array $arguments): Response {
        
        if(!isset($arguments['product'])) {
            return $response->withStatus(404);
        }
        
        $product = R::load('produto', $arguments['product']);
        
        $this->responseData['suppliers'] = $product
            ->withCondition(' desabilitado IS NULL ')
            ->sharedFornecedorList;
        
        return $this->container['view']->render(
            $response, 
            'supplier/product-receipt-list.twig', 
            $this->responseData
        );
    }
    
}
