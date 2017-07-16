<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Venda
 *
 * @author gusta
 */
class Venda extends AbstractModel implements \ModelInterface\Movimenta {

    public function cancelarMovimento(): bool {
        
    }

    public function gerarMovimentacao(): Movimento {

        $movement = R::dispense('movimento');

        $movement->valor = $this->bean->totalLiquido;
        $movement->direcao = Movimento::ENTRADA;
        $movement->data = new \DateTime;
        $movement->tipoMovimento = TipoMovimento::enumFactory('Venda');
        $movement->empresa = $this->bean->empresa;

        return $movement->box();
    }

    /**
     * Gera os dados da venda
     * @param \Slim\Http\Request $request
     * @param \RedBeanPHP\OODBBean $user
     * @param \RedBeanPHP\OODBBean $company
     */
    public function create(
    \Slim\Http\Request $request, \RedBeanPHP\OODBBean $user, \RedBeanPHP\OODBBean $company
    ) {

        $data = $request->getParsedBody();

        $this->loadProducts($data);

        $this->loadPaymentData($data);

        $this->bean->usuario = $user;

        $this->bean->empresa = $company;
        
        $this->bean->observacao = $data["observacao"];

        $this->checkForVerification();
    }

    public function update() {
        parent::update();
        
        if (!$this->bean->id) {

            $this->checkForVerification();

            $this->bean->movimento = $this->gerarMovimentacao();
        }
    }

    public function loadProducts(array $data) {

        $totalValue = 0.0;

        foreach ($data["produto"] as $productInfo) {

            $product = R::load('produto', $productInfo["id"]);

            $this->bean->link('venda_produto', [
                        'quantidade' => $productInfo["quantidade"],
                        'preco' => $product->valor
                    ])->produto = $product;

            $totalValue += ($product->valor * $productInfo["quantidade"]);
        }

        $this->bean->total = $totalValue;
    }

    public function loadPaymentData(array $data) {

        $this->bean->pagamento = R::load('pagamento', $data["metodoPagamento"]);

        //verifica se há desconto
        if ($data["desconto"] > 0) {
            $this->bean->desconto = $data["desconto"];
            $this->bean->totalLiquido = ($this->bean->total - $this->bean->desconto);
        } else {
            $this->bean->totalLiquido = $this->bean->total;
        }
        
        if ($this->bean->pagamento->parcelado) {
            $this->bean->xownParcelaList = $this->loadInstallments($data);
        }

        //verifica o troco
        if ($this->bean->pagamento->aceitaTroco) {
            $this->bean->recebido = $data["recebido"];
            if ($data["troco"] > 0) {
                $this->bean->troco = $this->bean->recebido - $this->bean->totalLiquido;
            }
        }
    }

    protected function loadInstallments($data): array {

        $installments = [];

        $installmentValue = round(($this->bean->totalLiquido / (int) $data["parcelamento"]), 2);

        for ($i = 1; $i <= $data["parcelamento"]; $i++) {

            $installment = R::dispense('parcela');

            $installment->sequencia = $i;
            $installment->valor = $installmentValue;
            $installment->vencimento = (new \DateTime())->add(new \DateInterval('P' . $i . 'M'));
            $installment->pago = false;

            $installments[] = $installment;
        }

        return $installments;
    }

    public function checkForVerification(): bool {

        $errors = [];

//        if (!V::notEmpty()->validate($this->bean->ownVendaProdutoList)) {
//            $errors["produtos"] = 'Não há produtos adicionados à venda!';
//        }

        if (!V::floatVal()->validate($this->bean->totalLiquido) ||
                !V::floatVal()->validate($this->bean->total)) {
            $errors["total"] = 'O calculo de valor total não foi executado corretamente.';
        }

        if ($this->bean->pagamento->parcelado) {
            if (!V::notEmpty()->validate($this->bean->xownParcelaList)) {
                $errors["parcelamento"] = 'Não foi possível verificar o parcelamento';
            }
        }

        if ($this->bean->pagamento->aceitaTroco) {
            if ($this->bean->recebido < $this->bean->totalLiquido) {
                $errors["recebido"] = 'O valor recebido é menor que o total da venda!';
            }
        }

        if (!empty($errors)) {
            throw new \Exception\SaleVerificationException($this->bean, $errors);
        }

        return true;
    }

}
