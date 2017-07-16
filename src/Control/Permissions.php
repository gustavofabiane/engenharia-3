<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Permissions
 *
 * @author gusta
 */
class Permissions extends AbstractController {

    public function employeePermissions(Request $request, Response $response, array $arguments): Response {

        $employee = R::load('usuario', $arguments['id']);
        
        $this->responseData['messages'] = $request->getAttribute('messages');

        if ($request->isPatch()) {

            try {

                $employee->updatePermissions($request->getParsedBody());
                
                $this->container['flash']->addMessage('success', 'Permissões do usuário atualizadas');
                
                return $response->withRedirect('/funcionarios/', 302);
                
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['message'] = $ex->getMessage();
                $this->responseData['errors'] = $ex->getErrors();
            }
        }
        
        $this->responseData['employee'] = $employee;
        $this->responseData['permissions'] = R::find('permissao', ' cargo_id < 4 ');

        return $this->container['view']->render($response, 'permissions/employee.twig', $this->responseData);
    }

}
