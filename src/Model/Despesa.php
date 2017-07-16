<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Despesa
 *
 * @author gusta
 */
class Despesa extends AbstractModel implements
\ModelInterface\Movimenta, \ModelInterface\Validavel {

    const APPROVED = 'Despesas aprovadas!';
    
    const DISAPPROVED = 'Despesas enviadas para revisão!';
    
    const APPROVE_ERROR = 'Não foi possível aprovar as despesas.';
    
    const DISAPPROVE_ERROR = 'Não foi possível enviar as despesas para revisão, '
                           . 'favor tentar novamente.';
    
    const CANCELLED_BATCH = 'Despesas canceladas com sucesso!';
    
    const CANCELLED_BATCH_ERROR = 'Não foi possível cancelar as despesas, '
                                . 'favor tentar novamente';

    /**
     * Cancela o movimento gerado pela despesa
     */
    public function cancelarMovimento(): bool {
        $this->movimento->cancelado = true;
        $this->movimento->dataCancelado = new \DateTime;
    }

    /**
     * Gera um movimento a partir dos dados da despesa
     * @return \Model\Movimento
     */
    public function gerarMovimentacao(): Movimento {
        
        $movement = R::dispense('movimento');
        
        $movement->valor = $this->bean->valor;
        $movement->tipoMovimento = TipoMovimento::enumFactory('Despesa');
        $movement->direcao = 'S';
        $movement->empresa = $this->bean->empresa;
        $movement->data = new \DateTime;
        
        return $movement->box();
    }

    /**
     * 
     * @param array $formData
     */
    public static function isValid(array $formData): array {
        
        $errors = [];
        
        if(!V::notEmpty()->validate($formData['tipoDespesa'])) {
            $errors['tipoDespesa'] = 'Favor informar o tipo da despesa (apenas letras)';
        }
        
        if(!V::notEmpty()->validate($formData['descricao'])) {
            $errors['descricao'] = 'Favor informar a descrição da despesa';
        }
        
        if(!V::floatVal()->validate($formData['valor'])) {
            $errors['valor'] = 'Favor informar um valor válido para a despesa';
        }
        
        if(!V::datetime('d/m/Y')->validate($formData['dtPagamento'])) {
            $errors['dtPagamento'] = 'Favor informar uma data para pagamento válida';
        }
        
        return $errors;
    }
    
    public function open() {
        $this->bean->statusDespesa;
        $this->bean->tipoDespesa;
    }
    
    /**
     * Prepara os dados da despesa a para persistência no banco dedados
     */
    public function update() {
        parent::update();
        
        if($this->bean->statusDespesa->id == 5) {
            $this->movimento = $this->gerarMovimentacao()->unbox();
        }
    }
    
    /**
     * Preenche o objeto Despesa
     * @param \Slim\Http\Request $request
     * @param \RedBeanPHP\OODBBean $company
     * @param \RedBeanPHP\OODBBean $user
     */
    public function populate(
        \Slim\Http\Request $request, 
        \RedBeanPHP\OODBBean $company = null, 
        \RedBeanPHP\OODBBean $user = null
    ) {
        $properties = parent::populate($request);
        
        if($user) {
            $this->usuario = $user;
        }
        
        if($company) {
            $this->empresa = $company;
        }
        
        if(!$this->bean->id || $this->bean->statusDespesa->id == 2) {
            $this->statusDespesa = StatusDespesa::enumFactory('Aberta');
        }
        
        foreach($properties as $property => $value) {
            switch ($property) {
                case 'tipoDespesa':
                    $this->bean->tipoDespesa = TipoDespesa::enumFactory($value);
                    break;
                case 'dtPagamento':
                    $this->bean->dtPagamento = \DateTime::createFromFormat('d/m/Y', $value);
                    break;
                case 'valor':
//                    $value = str_replace('.', '', $value);
//                    $value = str_replace(',', '.', $value);
                    $this->bean->valor = (double) $value;
                    break;
                default:
                    $this->bean->{$property} = $value;
            }
        }
    }
    
    /**
     * Cancela a despesa
     * Se houver movimento, cancela o movimento
     */            
    public function cancel() {
        $this->bean->cancelada_em = new \DateTime;
        $this->bean->statusDespesa = StatusDespesa::enumFactory("Cancelada");

        if($this->bean->movimento) {
            $this->cancelarMovimento();
        }
        
        R::store($this->bean);
    }
    
    /**
     * 
     * @param array $ids
     * @param \RedBeanPHP\OODBBean $manager
     * @return string
     */
    public function cancelAll(array $ids, \RedBeanPHP\OODBBean $manager): string {
        
        $bills = R::loadAll('despesa', $ids);
        
        R::begin();
        
        try {
            
            foreach($bills as $bill) {
                $bill->gerente = $manager;
                $bill->cancel();
            }
        
        } catch (\Exception $ex) {
            R::rollback();
            throw new \Exception(self::CANCELLED_BATCH_ERROR);
        }
        
        R::commit();
        
        return self::CANCELLED_BATCH;
    }

    /**
     * Aprova as despesas selecionadas através das ids
     * @param array $ids array com os ids das despesas
     * @return string $message mensagem de resposta
     * @throws \Exception
     */
    public function approve(array $ids, \RedBeanPHP\OODBBean $manager): string {

        $bills = R::loadAll('despesa', $ids);
            
        R::begin();

        try {

            foreach ($bills as $bill) {
                $bill->statusDespesa = StatusDespesa::enumFactory('Aprovada');
                $bill->gerente = $manager;
                R::store($bill);
            }
            
        } catch (\Exception $ex) {
            R::rollback();
            throw new \Exception(self::APPROVE_ERROR);
        }

        R::commit();

        return self::APPROVED;
    }

    /**
     * Desaprova as despesas selecionadas através das ids e as envia para revisão
     * @param array $ids array com os ids das despesas
     * @return string $message mensagem de resposta
     * @throws \Exception
     */
    public function disapprove(array $ids, \RedBeanPHP\OODBBean $manager): string {

        $bills = R::loadAll('despesa', $ids);

        R::begin();
        
        try {
            
            $status = StatusDespesa::enumFactory('Revisão');
            
            foreach($bills as $bill) {
                $bill->statusDespesa = StatusDespesa::enumFactory('Revisão');
                $bill->gerente = $manager;
                R::store($bill);
            }

//            R::storeAll($bills);
            
        } catch (\Exception $ex) {
            R::rollback();
            throw new \Exception(self::DISAPPROVE_ERROR);
        }

        R::commit();

        return self::DISAPPROVED;
    }
    
    public static function getTypes(array $data): string {
        
        $types = R::find('tipodespesa', ' name LIKE ? ', [ '%' . $data["query"] . '%']);
        
        $suggetions = [
            'query' => $data["query"],
            'suggestions' => []
        ];
        
        foreach($types as $type) {
            $suggetions["suggestions"][] = $type->name;
        }
        
        return json_encode($suggetions);
    }

}
