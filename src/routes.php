<?php

$app->get('/', Control\Home::class . ':index');

$app->get('/recursos/', Control\Home::class . ':resources');

/**
 * Rota de login
 */
$app->map(['GET', 'POST'], '/login/', Control\Login::class)->setName('login');

/**
 * Logout
 */
$app->get('/logout/', Control\Login::class . ':logout')->setName('logout');

/**
 * Rotas da Dashboard
 */
$app->group('/dashboard', function() {

    $this->get('/', Control\Dashboard::class);
            
})
->add(Middleware\CompanyAuthentication::class)
->add(Control\Login::class . ':checkLogged')
->add(new \Slim\Middleware\Session([
        'name' => 'user-session',
        'lifetime' => '120 minutes'
    ])
);

/**
 * Rotas de cadastro
 */
$app->group('/cadastro', function() {

    $this->map(['GET', 'POST'], '/conta/', Control\Register::class . ':account')->setName('register-account');

    $this->map(['GET', 'POST'], '/empresa/', Control\Register::class . ':company')->setName('register-company');

    $this->map(['GET', 'POST'], '/verificar/', Control\Register::class . ':verification')->setName('register-verify');

    $this->get('/finalizado/', Control\Register::class . ':finished')->setName('register-finished');

    $this->get('/cancelar/', Control\Register::class . ':cancel')->setName('register-cancel');
    
});

/**
 * Rotas de empresa
 */
$app->group('/empresa', function() {
    
    $this->group('/selecionar', function() {
        
        $this->get('/', Control\Company::class . ':select');

        $this->options('/', Control\Company::class . ':selected')->setName('select-company');
        
        $this->get('/{id:[0-9]+}/', Control\Company::class . ':selected')->setName('select-company-by-id');
    
    });

    $this->get('s/', Control\Company::class . ':ownCompanies')->setName('own-companies');
    
    $this->get('/{id:[0-9]+}/', Control\Company::class . ':view')->setName('view-company');
    
    $this->map(['GET', 'POST'], '/nova/', Control\Company::class . ':newCompany')->setName('new-company');
    
    $this->map(['GET', 'PUT'], '/alterar/{id:[0-9]+}/', Control\Company::class . ':update')->setName('update-company');
  
    $this->map(['GET', 'DELETE'], '/fechar/{id:[0-9]+}/', Control\Company::class . ':close')->setName('close-company');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/funcionario', function(){
    
    $this->get('s/', Control\Employee::class . ':companyEmployees')->setName('list-employees');
    
    $this->map(['GET', 'POST'], '/novo/', Control\Employee::class . ':newEmployee')->setName('new-employee');
    
    $this->map(['GET', 'PUT'], '/alterar/{id:[0-9]+}/', Control\Employee::class . ':updateEmployee')->setName('update-employee');
    
    $this->map(['GET', 'DELETE'], '/demitir/{id:[0-9]+}/', Control\Employee::class . ':dismissEmployee')->setName('dismiss-employee');
    
    $this->map(['GET', 'PATCH'], '/{id:[0-9]+}/permissoes/', Control\Permissions::class . ':employeePermissions')->setName('employee-permissions');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/categoria', function(){
    
    $this->get('s/', Control\Category::class . ':companyCategories')->setName('list-categories');
    
    $this->map(['GET', 'POST'], '/nova/', Control\Category::class . ':newCategories')->setName('new-categories');
    
    $this->map(['GET', 'PUT'], '/alterar/{id:[0-9]+}/', Control\Category::class . ':updateCategory')->setName('update-category');
    
    $this->map(['GET', 'DELETE'], '/desativar/{id:[0-9]+}/', Control\Category::class . ':disableCategory')->setName('disable-category');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/fornecedor', function(){
    
    $this->get('es/', Control\Supplier::class . ':companySuppliers')->setName('list-suppliers');
    
    $this->map(['GET', 'POST'],'/novo/', Control\Supplier::class . ':newSupplier')->setName('new-supplier');
    
    $this->map(['GET', 'PUT'],'/alterar/{id:[0-9]+}/', Control\Supplier::class . ':updateSupplier')->setName('update-supplier');
    
    $this->map(['GET', 'DELETE'],'/desativar/{id:[0-9]+}/', Control\Supplier::class . ':disableSupplier')->setName('disable-supplier');
    
    $this->get('es/fornecedores-recebimento-produto/[{product:[0-9]+}/]', Control\Supplier::class . ':productReceiptSuppliers')->setName('product-receipt-suppliers');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/despesa', function(){
    
    $this->get('s/', Control\Bill::class . ':companyBills')->setName('list-bills');
    
    $this->post('s/tipos/', Control\Bill::class . ':suggestTypes')->setName('suggest-types');
    
    $this->map(['GET', 'POST'], '/nova/', Control\Bill::class . ':newBill')->setName('new-bill');
    
    $this->map(['GET', 'PUT'], '/alterar/{id:[0-9]+}/', Control\Bill::class . ':updateBill')->setName('update-bill');
    
    $this->map(['GET', 'DELETE'], '/cancelar/{id:[0-9]+}/', Control\Bill::class . ':cancelBill')->setName('cancel-bill');
    
    $this->get('s/gestao/', Control\Bill::class . ':approving')->setName('approving-bills');
    
    $this->patch('/aprovacoes/[{choice}/]', Control\Bill::class . ':approvingChoice')->setName('approving-bills-choice');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/produto', function() {
    
    $this->get('s/', Control\Product::class . ':companyProducts')->setName('list-products');
    
    $this->map(['GET', 'POST'], '/novo/', Control\Product::class . ':newProduct')->setName('new-product');
    
    $this->map(['GET', 'PUT'], '/alterar/{id:[0-9]+}/', Control\Product::class . ':updateProduct')->setName('update-product');
    
    $this->get('/{id:[0-9]+}/', Control\Product::class . ':productDetails')->setName('view-product');
    
    $this->map(['GET', 'PATCH'], 's/recebimento/', Control\Product::class . ':productReceipt')->setName('product-receipt');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/metodos-de-pagamento', function() {
    
    $this->get('/', Control\PaymentMethod::class . ':listMethods')->setName('payment-methods');
    
    $this->put('/atualizar/', Control\PaymentMethod::class . ':updateCompanyMethods')->setName('update-company-pay-methods');
    
    $this->patch('/formulario/{id:[0-9]+}/', Control\PaymentMethod::class . ':paymentMethodForm')->setName('method-form');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');

$app->group('/venda', function() {
    
    $this->map(['GET', 'PUT'], '/nova/', Control\Sale::class . ':newSale')->setName('new-sale');
    
    $this->post('/checar-estoque/', Control\Sale::class . ':checkStock')->setName('check-stock');
    
    $this->get('/verificar/', Control\Sale::class . ':verifySale')->setName('verify-sale');
    
    $this->put('/efetuar/', Control\Sale::class . ':storeSale')->setName('store-sale');
    
    $this->delete('/cancelar/[{id:[0-9]+}/]', Control\Sale::class . ':cancelSale')->setName('cancel-sale');
    
    $this->get('s/', Control\Sale::class . ':listSales')->setName('list-sales');
    
    $this->get('s/funcionario/{id:[0-9]+}/', Control\Sale::class . ':listSales')->setName('list-sales-employee');
    
    $this->get('/ver/{id:[0-9]+}/', Control\Sale::class . ':viewSale')->setName('view-sale');
    
})
->add(Middleware\FlashMessageCatcher::class)
->add(Control\Login::class . ':checkLogged');
