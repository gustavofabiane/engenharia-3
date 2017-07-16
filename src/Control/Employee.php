<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Employee
 *
 * @author gusta
 */
class Employee extends AbstractController {

    public function companyEmployees(Request $request, Response $response): Response {

        $employees = R::find('usuario', ' demitido IS NULL AND empresa_id = ? AND cargo_id < 4 ORDER BY id DESC ', [$this->company->id]);

        $this->responseData['messages'] = $request->getAttribute('messages');
        
        $this->responseData['employees'] = $employees;

        return $this->container['view']->render(
                        $response, 'employee/list.twig', $this->responseData
        );
    }

    public function newEmployee(Request $request, Response $response): Response {

        if ($request->isPost()) {

            $employee = R::dispense('usuario');

            try {
            
                $employee->populate($request, $this->company);
                
                $employeeId = R::store($employee);

                $this->container['flash']->addMessage(
                        'info', 
                        'Novo funcionário salvo. Favor selecionar suas permissões.'
                );

                return $response->withRedirect('/funcionario/' . $employeeId . '/permissoes/', 302);
                
            } catch (\Exception\FormValidationException $ex) {

                $this->responseData["errors"] = $ex->getErrors();
                $this->responseData["message"] = $ex->getMessage();

                $this->responseData["employee"] = $employee;
            }
        }

        $this->responseData['jobs'] = R::find('cargo', ' id <> 4 ORDER BY id DESC ');
        $this->responseData['states'] = R::findAll('estado', ' ORDER BY descricao ');

        return $this->container['view']->render($response, 'employee/new.twig', $this->responseData);
    }

    public function updateEmployee(Request $request, Response $response, array $arguments): Response {

        $employee = R::findOne('usuario', ' id = ? AND empresa_id = ? ', [$arguments['id'], $this->company->id]);

        if ($request->isPut()) {

            try {

                $employee->populate($request);

                R::store($employee);

                $this->container['flash']->addMessage(
                        'success', 'Os dados do funcionário foram atualizados com sucesso'
                );

                return $response->withRedirect('/funcionarios/', 302);
                
            } catch (\Exception\FormValidationException $ex) {

                $this->responseData["errors"] = $ex->getErrors();
                $this->responseData["message"] = $ex->getMessage();
            }
        }

        $this->responseData['jobs'] = R::find('cargo', ' id <> 4 ORDER BY id DESC ');
        $this->responseData['states'] = R::findAll('estado', ' ORDER BY descricao ');

        $this->responseData['employee'] = $employee;

        return $this->container['view']->render($response, 'employee/update.twig', $this->responseData);
    }

    public function dismissEmployee(Request $request, Response $response, array $arguments): Response {

        $employee = R::load('usuario', $arguments['id']);

        if ($request->isDelete()) {

            $employee->dismiss();

            $this->container['flash']->addMessage('success', 'Funcionário demitido com sucesso');

            return $response->withRedirect('/funcionarios/', 302);
        }

        $this->responseData['employee'] = $employee;

        return $this->container['view']->render($response, 'employee/dismiss.twig', $this->responseData);
    }

}
