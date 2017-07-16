<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Usuario
 *
 * @author gusta
 */
class Usuario extends AbstractModel implements \ModelInterface\Validavel {

    /**
     * Popula a bean a partir dos dados da requisição
     * @param \Slim\Http\Request $request
     */
    public function populate(\Slim\Http\Request $request, \RedBeanPHP\OODBBean $company = null) {
        
        if($company) {
            $this->bean->empresa = $company;
        }

        $properties = parent::populate($request);

        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'cargo':
                    $this->bean->cargo = R::load('cargo', $value);
                    break;
                case 'estado':
                    $this->bean->estado = R::load('estado', $value);
                    break;
                case 'genero':
                    $this->bean->genero = R::enum('genero:' . $value);
                    break;
                case 'dtNascimento':
                    $this->bean->dtNascimento = \DateTime::createFromFormat('d/m/Y', $value);
                    break;
                default:
                    $this->bean->{$property} = $value;
            }
        }
        
        if(!empty($this->bean->errors)) {
            throw new \Exception\FormValidationException($this->bean->errors, 'Dados do formulário inválidos');
        } else {
            unset($this->bean->errors);
        }
    }

    /**
     * Carrega o Bean resolvendo o problema do lazy load para relacionamentos no TWIG
     */
    public function open() {
        $this->bean->estado;
        $this->bean->cargo;
        $this->bean->genero;
        $this->bean->sharedPermissionList;
    }

    /**
     * Valida os dados de login
     * @param string $username
     * @param string $password
     * @return \RedBeanPHP\OODBBean
     * @throws \Exception
     */
    public static function validate(string $username, string $password): \RedBeanPHP\OODBBean {

        $user = R::findOne('usuario', ' login = :username ', [':username' => $username]);

        if ($user == null) {
            throw new \Exception('Usuário inválido!');
        }

        if (!\Vendor\BCrypt\BCrypt::checkPassword($password, $user->senha)) {
            throw new \Exception('Senha inválida!');
        }

        return $user;
    }

    /**
     * Salva o usuário como dono de uma empresa
     * @param \RedBeanPHP\OODBBean $company
     * @return boolean
     */
    public function saveAsCompanyOwner(\RedBeanPHP\OODBBean $company = null): bool {

        R::begin();

        $this->bean->cargo = R::load('cargo', 4);

        $userId = R::store($this->bean);

        $this->bean = R::load('usuario', $userId);

        $company->dono = $this->bean;
        
        $initialCash = $company->saldoInicial;
        
        unset($company->saldoInicial);

        if (!is_null($company)) {

            $companyId = R::store($company);

            if (!is_numeric($companyId)) {
                R::rollback();
                return false;
            }
            
            $company = R::load('empresa', $companyId);
            
            $movement = $company->generateInitialCash(floatval($initialCash));
            R::store($movement);
        }

        R::commit();

        return true;
    }

    /**
     * Verifica se o usuário é dono de alguma empresa
     * @return bool
     */
    public function isOwner(): bool {
        return ($this->bean->cargo->id == 4);
    }

    /**
     * Valida os dados do Usuário
     * @param array $formData
     * @return array $errors
     * @throws \Exception\FormValidationException
     */
    public static function isValid(array $formData): array {

        $errors = self::isValidAddress($formData);

        if (!V::cpf()->validate($formData['cpf'])) {
            $errors['cpf'] = 'CPF Inválido, favor verificar';
        }

        if (!V::dateTime()
                        ->between('1900-01-01', '2100-12-31')
                        ->validate(\DateTime::createFromFormat('d/m/Y', $formData['dtNascimento']))
        ) {
            $errors['dtNascimento'] = 'Favor informar um data de nascimento válida.';
        }

        if (!V::email()->validate($formData['email'])) {
            $errors['email'] = 'Favor informar um e-mail válido.';
        }

        if (!V::notEmpty()->validate($formData['nome'])) {
            $errors['nome'] = 'Favor informar seu nome';
        }

        if (!V::notEmpty()->validate($formData['sobrenome'])) {
            $errors['sobrenome'] = 'Favor informar seu sobrenome';
        }

        if (isset($formData["cargo"])) {
            if (!V::numericVal()->between(0, 4)->validate($formData['cargo'])) {
                $errors['cargo'] = 'Favor selecionar um cargo para o funcionário';
            }
        }

        return $errors;
    }

    /**
     * Valida os dados do formulário para um novo usuário
     * @static
     * @param array $formData
     * @return array
     */
    public static function isValidNew(array $formData): array {
        $errors = self::isValid($formData);

        if (!V::alpha()->lowercase()->length(1, 20)->validate($formData['login'])) {
            $errors['login'] = 'Favor informar um login válido. '
                    . '(Apenas letras minúsculas, com no máximo 20 caracteres)';
        }

        $findLogin = R::find('usuario', ' login = ? ', [$formData['login']]);
        if (!empty($findLogin)) {
            $errors['login'] = 'Este nome de usuário já está em uso, favor escolher outro.';
        }

        $findEmail = R::find('usuario', ' email = ? ', [$formData['email']]);
        if (!empty($findEmail)) {
            $errors['email'] = 'Este e-mail já está em uso, e não está disponível para cadastro.';
        }

        $validPass = self::isValidPassword($formData['senha'], $formData['confirma']);
        if (is_string($validPass)) {
            $errors['senha'] = $validPass;
        }

        return $errors;
    }

    /**
     * Valida uma nova conta
     * @param array $formData
     * @return array
     */
    public static function isValidNewAccount(array $formData): array {
        
        $errors = self::isValidNew($formData);

        unset($errors["cargo"]);

        return $errors;
    }

    /**
     * Valida os dados para atualização de Usuário
     * @static
     * @param array $formData
     * @return array
     */
    public static function isValidUpdate(array $formData): array {
        return self::isValid($formData);
    }

    /**
     * Valida os dados do usuário
     */
    public function update() {

        parent::update();

        if (!$this->bean->id) {
            $this->bean->sharedPermissaoList = R::find('permissao', ' cargo_id <= ? ', [$this->bean->cargo->id]);
            unset($this->bean->confirma);
            $this->bean->senha = \Vendor\BCrypt\BCrypt::hashPassword($this->bean->senha, 8);
        }
    }

    /**
     * Verifica se a senha informada no formulário é valida
     * @return boolean|string
     */
    protected static function isValidPassword(string $senha, string $confirma) {
        if ($senha == '') {
            return 'Favor informar uma senha.';
        }
        if ($confirma == '') {
            return 'Favor confirmar sua senha.';
        }
        if ($senha != $confirma) {
            return 'Sua senha não foi confirmada corretamente.';
        }
        if (preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,16}$/', $senha) == 0) {
            return 'Sua senha deve conter pelo menos: uma letra maiúscula, uma letra minúscula e um digito';
        }
        return true;
    }

    /**
     * Executa o procedimento de mitir um funcionário
     */
    public function dismiss() {

        $this->bean->demitido = true;
        $this->bean->demitido_em = new \DateTime;

        R::store($this->bean);
    }

    /**
     * Verifica se o funcionário tem a permissão recebida
     * @param \RedBeanPHP\OODBBean $permissionChecked
     * @return bool
     */
    public function hasPermission(\RedBeanPHP\OODBBean $permissionChecked): bool {
        $permissions = [];
        foreach ($this->bean->sharedPermissaoList as $permission) {
            $permissions[] = $permission->id;
        }

        return in_array($permissionChecked->id, $permissions);
    }
    
    /**
     * Atualiza as permissoes do usuário
     * @param array $permissions
     * @throws \Exception\FormValidationException
     */
    public function updatePermissions(array $permissions) {
        
        try {
            
            $this->bean->sharedPermissaoList = R::loadAll('permissao', $permissions["permissoes"]);

            R::store($this->bean);
            
        } catch (\Exception $ex) {
            throw new \Exception\FormValidationException([], "Não foi possível salvar as permissões");
        }
    }

}
