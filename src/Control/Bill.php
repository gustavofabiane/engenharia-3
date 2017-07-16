<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Bill
 *
 * @author gusta
 */
class Bill extends AbstractController {

    /**
     * Lista as despesas da empresa
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function companyBills(Request $request, Response $response): Response {

        $this->responseData['messages'] = $request->getAttribute('messages', []);

        $this->responseData['bills'] = R::find(
            'despesa', 
            ' empresa_id = ? AND status_despesa_id <> 5 AND usuario_id = ? ', 
            [$this->company->id, $this->user->id]
        );

        return $this->container['view']->render($response, 'bill/list.twig', $this->responseData);
    }

    /**
     * Gera nova despesa
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function newBill(Request $request, Response $response): Response {

        if ($request->isPost()) {
            $bill = R::dispense('despesa');
            $bill->populate($request, $this->company, $this->user);

            try {
                R::store($bill);

                $this->container['flash']->addMessage(
                    'success', 
                    'Despesa criada com sucesso, aguardando aprovação.'
                );

                return $response->withRedirect('/despesas/', 302);
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['message'] = $ex->getMessage();
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['bill'] = $bill;
            }
        }

        return $this->container['view']->render($response, 'bill/new.twig', $this->responseData);
    }

    /**
     * Atualiza os dados da despesa
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function updateBill(Request $request, Response $response, array $arguments): Response {

        $bill = R::load('despesa', $arguments['id']);

        if ($request->isPut()) {

            $bill->populate($request, $this->company);

            try {
                R::store($bill);

                $this->container['flash']->addMessage(
                    'success', 
                    'Despesa criada com sucesso, aguardando aprovação.'
                );

                return $response->withRedirect('/despesas/', 302);
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['message'] = $ex->getMessage();
                $this->responseData['errors'] = $ex->getErrors();
            }
        }

        $this->responseData['bill'] = $bill;

        return $this->container['view']->render($response, 'bill/update.twig', $this->responseData);
    }

    /**
     * Cancela a despesa
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function cancelBill(Request $request, Response $response, array $arguments): Response {

        $bill = R::load('despesa', $arguments['id']);

        if ($request->isDelete()) {

            $bill->cancel();

            $this->container['flash']->addMessage(
                'success', 
                'Despesa cancelada com sucesso'
            );

            return $response->withRedirect('/despesas/', 302);
        }

        $this->responseData['bill'] = $bill;

        return $this->container['view']->render($response, 'bill/cancel.twig', $this->responseData);
    }

    /**
     * Mostra as despesas disponíveis para aprovação ou veto
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function approving(Request $request, Response $response, array $arguments): Response {

        $this->responseData['messages'] = $request->getAttribute('messages', []);
        
        $this->responseData['bills'] = R::find(
            'despesa',
            ' empresa_id = ? AND status_despesa_id = 1 ',
            [$this->company->id]
        );

        return $this->container['view']->render($response, 'bill/approving.twig', $this->responseData);
    }

    /**
     * Aprova ou desaprova um lote de despesas selecionadas
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function approvingChoice(Request $request, Response $response, array $arguments): Response {

        $billModel = new \Model\Despesa();
        
        $data = $request->getParsedBody();
        try{
            
            $result = $billModel->{$arguments['choice']}($data['despesas'], $this->user);
            
            $this->container['flash']->addMessage('success', $result);
            
        } catch (\Exception $ex) {
            $this->container['flash']->addMessage('warning', $ex->getMessage());
        } finally {
            return $response->withRedirect('/despesas/gestao/', 302);
//            return $response;
        }
    }

    public function suggestTypes(Request $request, Response $response): Response {
        
        $requestData = $request->getParsedBody();
        
        $suggest = \Model\Despesa::getTypes($requestData);
        
        $response->getBody()->write($suggest);
        
        return $response->withHeader("Content-type", 'application/json');
    }

}
