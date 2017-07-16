<?php

namespace Model;

/**
 * Description of MetodoPagamento
 *
 * @author gusta
 */
class Pagamento extends AbstractModel {

    /**
     * Define o formulário de venda do método
     * @return string
     */
    public function getFormType(): string {

        $form = ($this->bean->parcelado) ? 'installment-form' : 'in-cash-form';

        return 'payment-method/' . $form . '.twig';
    }

    public function verifySaleData(array $saleData): array {

        $total = (float) $saleData["total"];

        $result = [
            'total' => $total
        ];

        if (isset($saleData["desconto"])) {
            $result["discountTotal"] = $total = ($total - (float) $saleData["desconto"]);
            $result["discount"] = (float) $saleData["desconto"];
        }

        if ($this->bean->parcelado) {
            $result["installments"] = $this->calcInstallments($total);
        }

        return $result;
    }

    protected function calcInstallments(float $value): array {

        $installmentCount = 0;

        $installments = [];

        do {

            $installmentCount++;

            $installmentValue = $value / $installmentCount;

            $installments[$installmentCount] = $installmentValue;
            
        } while ($installmentValue > $this->bean->minValorParcela);
        
        unset($installments[$installmentCount]);

        return $installments;
    }

}
