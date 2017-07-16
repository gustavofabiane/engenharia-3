<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Product
 *
 * @author gusta
 */
class Product extends AbstractController {
    
    public function companyProducts(Request $request, Response $response): Response {
        
        $this->responseData['products'] = $this->company->ownProdutoList;
        
        $this->responseData['messages'] = $request->getAttribute('messages', []);
        
        return $this->container['view']->render($response, 'product/list.twig', $this->responseData);
    }

    /**
     * Cria um novo produto
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function newProduct(Request $request, Response $response): Response {

        if ($request->isPost()) {

            $product = R::dispense('produto');

            $product->populate($request, $this->company, $this->user);

            try {

                R::store($product);

                $this->container['flash']->addMessage(
                        'success', 'Novo produto salvo com sucesso'
                );
                
                return $response->withRedirect('/produtos/', 302);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData["errors"] = $ex->getErrors();
                $this->responseData["message"] = $ex->getMessage();
                $this->responseData´["product"] = $product;
            }
        }
        
        $this->responseData["categories"] = $this->company->withCondition(' desabilitada IS NULL ')->ownCategoriaList;
        
        $this->responseData["suppliers"] = $this->company->withCondition(' desabilitado IS NULL ')->ownFornecedorList;
        
        return $this->container['view']->render($response, 'product/new.twig', $this->responseData);
    }

    /**
     * Atualiza os dados de um produto
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function updateProduct(Request $request, Response $response, array $arguments): Response {

        $product = R::load('produto', $arguments['id']);

        if ($request->isPut()) {

            $product->populate($request, $this->company, $this->user);

            try {

                R::store($product);

                $this->container['flash']->addMessage(
                    'success', 
                    'Os dados do produto foram alterados com sucesso!'
                );
                
                return $response->withRedirect('/produtos/', 302);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData["errors"] = $ex->getErrors();
                $this->responseData["message"] = $ex->getMessage();
            }
        }
        
        $this->responseData["product"] = $product;
        
        $this->responseData["categories"] = $this->company
                ->withCondition(' desabilitada IS NULL ')
                ->ownCategoriaList;
        
        $this->responseData["suppliers"] = $this->company
                ->withCondition(' desabilitado IS NULL ')
                ->ownFornecedorList;
        
        return $this->container['view']->render($response, 'product/update.twig', $this->responseData);
    }
    
    /**
     * Detalhes do produto
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function productDetails(Request $request, Response $response, array $arguments): Response {
        
        $this->responseData['product'] = $product = R::load('produto', $arguments['id']);
        
        $this->responseData['sales'] = $product->with(' ORDER BY criado_em DESC ')->ownVendaProdutoList;
        
        return $this->container['view']->render($response, 'product/details.twig', $this->responseData);
    }

    /**
     * Recebimento de produto
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function productReceipt(Request $request, Response $response): Response {

        if($request->isPatch()) {
            
            $productModel = new \Model\Produto;
            
            $data = $request->getParsedBody();

            list($level, $message) = $productModel->receive($data);

            $this->container['flash']->addMessage($level, $message);
            
            return $response->withRedirect('/produtos/', 302);
        }
        
        $this->responseData['products'] = $this->company->ownProdutoList;
        
        return $this->container['view']->render($response, 'product/receipt.twig', $this->responseData);
    }
}
