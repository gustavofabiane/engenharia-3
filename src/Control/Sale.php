<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Sale
 *
 * @author gusta
 */
class Sale extends AbstractController {

    public function newSale(Request $request, Response $response): Response {

        if ($request->isPut()) {

            $sale = R::dispense('venda');

            try {

                $sale->create($request, $this->user, $this->company);

                $this->container['session']->set('sale', $sale);

                return $response->withRedirect('/venda/verificar/', 302);
            } catch (\Exception\SaleVerificationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['sale'] = $ex->getSaleData();
            }
        }

        $this->responseData["products"] = $this->company->productsForSale();

        $this->responseData["methods"] = $this->company->with(' ORDER BY nome ')->sharedPagamentoList;

        return $this->container['view']->render($response, 'sale/new.twig', $this->responseData);
    }

    public function verifySale(Request $request, Response $response): Response {

        $sale = $this->container['session']->get('sale');

        try {

            $sale->checkForVerification();

            $this->responseData['sale'] = $sale;
//            echo"<pre>";
//            var_dump($sale);

            $this->responseData['saleProducts'] = $sale->ownVenda_produtoList;
        } catch (\Exception\SaleVerificationException $ex) {
            $this->container['flash']->addMessage($ex->getMessage());
            return $response->withRedirect('/venda/nova/', 302);
        }

        return $this->container['view']->render($response, 'sale/check.twig', $this->responseData);
    }

    public function storeSale(Request $request, Response $response): Response {

        $sale = $this->container['session']->get('sale');

        try {

            $saleId = R::store($sale);

            $this->container['flash']->addMessage('success', 'Venda Efetuada com sucesso!');

            $this->container['session']->delete('sale');

            return $response->withRedirect('/venda/ver/' . $saleId . '/', 302);
        } catch (\Exception\SaleVerificationException $ex) {

            $this->container['flash']->addMessage('danger', $ex->getMessage());

            return $response->withRedirect('/venda/nova/', 302);
        }
    }

    public function viewSale(Request $request, Response $response, array $arguments): Response {

        $sale = R::load('venda', $arguments["id"]);

        $this->responseData['sale'] = $sale;

        $this->responseData['saleProducts'] = $sale->ownVendaProdutoList;

        return $this->container['view']->render($response, 'sale/view.twig', $this->responseData);
    }

    public function cancelSale(Request $request, Response $response): Response {
        return $response;
    }

    public function checkStock(Request $request, Response $response): Response {

        $model = new \Model\Produto;

        $body = $model->checkProductStock($request->getParsedBody());

        $response = $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write($body);

        return $response;
    }

    public function listSales(Request $request, Response $response, array $arguments): Response {

        $this->responseData['sales'] = R::find('venda', ' usuario_id = ? ', [$this->user->id]);

        return $this->container['view']->render($response, 'sale/list-employee.twig', $this->responseData);
    }

}
