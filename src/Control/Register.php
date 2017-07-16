<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Cadastro
 *
 * @author gusta
 */
class Register extends AbstractController {

    public function cancel(Request $request, Response $response): Response {
        \SlimSession\Helper::destroy();
        return $response->withRedirect('/login/', 302);
    }

    /**
     * Registra o usuário no procedimento de cadastro
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function account(Request $request, Response $response): Response {

        $this->responseData['title'] .= ' - Nova Conta';

        $this->container['setupRedBean'];

        if ($request->isPost()) {

            $user = R::dispense('usuario');


            try {
                $user->populate($request);

                $this->container['session']->set('user', $user);

                return $response->withRedirect('/cadastro/empresa/', 301);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['user'] = $user;
            }
        } else {

            $session = $this->container['session'];

            if ($session->exists('user')) {
                $this->responseData['user'] = $session->get('user');
            }
        }

        $this->responseData['states'] = R::findAll('estado', ' ORDER BY descricao ');

        return $this->container['view']->render($response, 'register/account.twig', $this->responseData);
    }

    /**
     * Registra a empresa no processo de cadastro
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function company(Request $request, Response $response): Response {

        $session = $this->container['session'];

        if (!$session->exists('user')) {
            return $response->withRedirect('/cadastro/conta/', 302);
        }

        $this->container['setupRedBean'];

        $user = $session['user'];


        $this->responseData['title'] .= ' - Nova Empresa';

        if ($request->isPost()) {

            $company = R::dispense('empresa');
            
            try {

                $company->populate($request);

                $session->set('company', $company);

                return $response->withRedirect('/cadastro/verificar/', 302);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['company'] = $company;
            }
        } else {
            if ($session->exists('company')) {
                $this->responseData['company'] = $session->get('company');
            }
        }

        $this->responseData['user'] = $user;

        $this->responseData['states'] = R::findAll('estado', ' ORDER BY descricao ');

        return $this->container['view']->render($response, 'register/company.twig', $this->responseData);
    }

    /**
     * Verifica os dados do cadastro e executa o procedimento para salvar os mesmos na confirmação
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function verification(Request $request, Response $response): Response {

        $session = $this->container['session'];

        if (!$session->exists('user')) {
            return $response->withRedirect('/cadastro/conta/', 302);
        }

        $this->responseData['title'] .= ' - Verificação';

        $this->container['setupRedBean'];

        $user = $session->get('user');

        if ($session->exists('company')) {
            $company = $session->get('company');
        }

        if ($request->isPost()) {

            try {

                $user->saveAsCompanyOwner(($company) ? $company : null);

                return $response->withRedirect('/cadastro/finalizado/', 302);
            } catch (\Exception $ex) {
                $this->responseData['error'] = 'Não foi possível salvar seus dados';
                $this->responseData['exception'] = $ex;
            }
        }

        $this->responseData['user'] = $user;
        $this->responseData['company'] = $company;

        return $this->container['view']->render($response, 'register/verification.twig', $this->responseData);
    }

    /**
     * Finaliza o cadastro de usuário
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function finished(Request $request, Response $response): Response {

        $this->responseData['title'] .= ' - Cadastro Realizado';

        $this->container['setupRedBean'];

        $session = $this->container["session"];

        $this->responseData['user'] = $session->get('user');

        if ($session->exists('company')) {
            $this->responseData['company'] = $session->get('company');
        }

        \SlimSession\Helper::destroy();

        return $this->container['view']->render($response, 'register/finished.twig', $this->responseData);
    }

}
