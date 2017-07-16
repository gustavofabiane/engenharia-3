<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Category
 *
 * @author gusta
 */
class Category extends AbstractController {

    /**
     * Lista a categorias cadastradas pela empresa
     * @param Request $request
     * @param Response $response
     */
    public function companyCategories(Request $request, Response $response): Response {

        $this->responseData['messages'] = $request->getAttribute('messages');
        
        $this->responseData['categories'] = R::find(
            'categoria', 
            ' empresa_id = ? AND desabilitada IS NULL ',
            [$this->company->id]
        );
        
        return $this->container['view']->render($response, 'category/list.twig', $this->responseData);
    }

    /**
     * Cria novas categorias
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function newCategories(Request $request, Response $response): Response {

        if ($request->isPost()) {

            try {

                $categories = \Model\Categoria::dispenseBatch($request, $this->company);

                \Model\Categoria::storeBatch($categories);

                $this->container['flash']->addMessage('success', 'Categorias criadas com sucesso!');

                return $response->withRedirect('/categorias/', 302);
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['errors'] = $ex->getMessage();
                $this->responseData['categories'] = $categories;
            }
        }

        return $this->container['view']->render($response, 'category/new.twig', $this->responseData);
    }

    /**
     * Atualiza os dados da categoria
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function updateCategory(Request $request, Response $response, array $arguments): Response {

        $category = R::load('categoria', $arguments['id']);

        if ($request->isPut()) {

            $category->populate($request);

            try {

                R::store($category);

                $this->container['flash']->addMessage(
                        'success', 'Categoria atualizada!');

                return $response->withRedirect('/categorias/', 302);
            } catch (\Exception\FormValidationException $ex) {
                $this->responseData['errors'] = $ex->getErrors();
                $this->responseData['message'] = $ex->getMessage();
            }
        }

        $this->responseData['category'] = $category;

        return $this->container['view']->render($response, 'category/update.twig', $this->responseData);
    }

    /**
     * Desabilita a categoria e seus produtos
     * @param Request $request
     * @param Response $response
     * @param array $arguments
     * @return Response
     */
    public function disableCategory(Request $request, Response $response, array $arguments): Response {

        $category = R::load('categoria', $arguments['id']);

        if ($request->isDelete()) {
            
            $category->disable();

            $this->container['flash']->addMessage(
                    'success', 'Categoria desabilitada'
            );

            return $response->withRedirect('/categorias/', 302);
        }
        
        $this->responseData['category'] = $category;

        return $this->container['view']->render($response, 'category/disable.twig', $this->responseData);
    }

}
