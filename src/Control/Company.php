<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Company
 *
 * @author gusta
 */
class Company extends AbstractController {

    /**
     * Lista as empresas
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function ownCompanies(Request $request, Response $response): Response {

        $this->responseData['title'] .= ' - Empresas';

        $this->responseData['companies'] = R::find(
                        'empresa', ' dono_id = ? ', [$this->user->id]
        );

        return $this->container['view']->render($response, 'company/companies.twig', $this->responseData);
    }

    /**
     * Visualizar Dados da Empresa
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function view(Request $request, Response $response, array $arguments): Response {

        $company = R::load('empresa', $arguments['id']);

        $this->responseData['title'] .= ' - ' . $company->fantasia;

        $this->responseData['messages'] = $request->getAttribute('messages');

        $user = $this->container['session']->get('user');

        if ($user->id == $company->fetchAs('usuario')->dono->id ||
                $user->empresa->id == $company->id) {

            $this->responseData['company'] = $company;

            return $this->container['view']->render($response, 'company/view.twig', $this->responseData);
        }

        throw new \Exception("Usuário não autorizado");
    }

    /**
     * Atualiza os dados da empresa
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function update(Request $request, Response $response, array $arguments): Response {

        $company = R::load('empresa', $arguments['id']);

        if (!$company->id) {
            throw new \Exception("Empresa não encontrada");
        }

        if ($request->isPut()) {

            try {

                $company->setAttributes($request->getParsedBody());

                R::store($company);

                $this->container['flash']->addMessage(
                        'success', 'Os dados da empresa foram atualizados com sucesso'
                );

                return $response->withRedirect('/empresa/' . $company->id . '/', 200);
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData["errors"] = $ex->getErrors();
                $this->responseData["mensagem"] = $ex->getMessage();
            }
        }

        $this->responseData["company"] = $company;
        $this->responseData["states"] = R::findAll('estado');

        return $this->container['view']->render($response, 'company/update.twig', $this->responseData);
    }

    public function close(Request $request, Response $response, array $arguments): Response {

        $company = R::load('empresa', $arguments['id']);

        if ($request->isDelete()) {
            
            $company->fechada = true;
            $company->fechada_em = new \DateTime;
            
            R::store($company);
            
            $this->container['flash']->addMessage('success', 'Empresa fechada com sucesso');
            
            return $response->withRedirect('/empresa/' . $company->id . '/', 302);
        }
        
        $this->responseData['company'] = $company;

        return $this->container['view']->render($response, 'company/close.twig', $this->responseData);
    }

    /**
     * Carrega as opções para seleção de empresa
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function select(Request $request, Response $response): Response {

        $session = $this->container['session'];

        $companies = R::count('empresa', ' dono_id = ? ', [$this->user->id]);

        if ($companies == 0 && $this->user->isOwner()) {

            return $response->withRedirect('/empresa/nova/', 302);
        } else if ($companies == 1 && $this->user->isOwner()) {

            $session->set('company', R::findOne('empresa', ' dono_id = ? ', [$this->user->id]));
        } else if (!$this->user->isOwner()) {

            $session->set('company', $this->user->empresa);
        } else {
            return $response->withStatus(404, "Usuário sem empresa definida e/ou não selecionada");
        }

        if ($session->exists('company')) {
            return $response->withRedirect('/dashboard/', 302);
        }

        $this->responseData['companies'] = $companies;
        $this->responseData['user'] = $this->user;

        return $this->container['view']->render($response, 'company/select.twig', $this->responseData);
    }

    /**
     * Seleciona empresa definida pelo usuário
     * @param Request $request
     * @param Response $response
     */
    public function selected(Request $request, Response $response, array $arguments): Response {

        if ($request->isGet()) {

            $companyId = $arguments['id'];
            $company = R::findOne('empresa', ' id = ? ', [$companyId]);

            $redirectTo = '/empresas/';
        } else {

            $requestData = $request->getParsedBody();
            $company = R::findOne("empresa", ' id = ? ', [$requestData["company"]]);

            $redirectTo = '/dashboard/';
        }

        if ($company == null) {
            return $response->withRedirect('/empresa/selecionar/', 203);
        }

        $this->container['session']->set('company', $company);

        return $response->withRedirect($redirectTo, 202);
    }

}
