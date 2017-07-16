<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of PaymentMethod
 *
 * @author gusta
 */
class PaymentMethod extends AbstractController {

    public function listMethods(Request $request, Response $response): Response {

        $this->responseData['messages'] = $request->getAttribute('messages', []);
        
        $this->responseData['methods'] = R::findAll('pagamento', ' ORDER BY nome ');

        $this->responseData['companyMethods'] = $this->company->sharedPagamentoList;

        return $this->container['view']->render($response, 'payment-method/list.twig', $this->responseData);
    }

    public function updateCompanyMethods(Request $request, Response $response): Response {

        try {

            $this->company->updatePaymentMethods($request, $this->user);

            $this->container['flash']->addMessage('success', 'Métodos de Pagamento Atualizados!');

            return $response->withRedirect('/metodos-de-pagamento/', 302);
        } catch (\Exception $ex) {

            $this->container['flash']->addMessage('danger', 'Oops, não foi possível atualizar '
                    . 'os métodos de pagamento, favor tentar novamente!');

            return $response->withRedirect('/metodos-de-pagamento/', 302);
        }
    }

    public function paymentMethodForm(Request $request, Response $response, array $arguments): Response {
        
        if ($request->isXhr()) {
            
            $method = R::load('pagamento', $arguments['id']);
            
            $form = $method->getFormType();
            
            $saleData = $method->verifySaleData($request->getParsedBody());
            
            $this->responseData["saleData"] = $saleData;
            
            $this->responseData["method"] = $method;
            
            return $this->container['view']->render($response, $form, $this->responseData);
            
        } else {
            throw new \Exception("Apenas requisições XHR");
        }
    }

}
