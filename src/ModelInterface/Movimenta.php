<?php

namespace ModelInterface;

/**
 *
 * @author gusta
 */
interface Movimenta {

    /**
     * Deve gerar um movimento a partir da bean vinculada ao model;
     * @return \Model\Movimento O movimento gerado
     */
    public function gerarMovimentacao(): \Model\Movimento;

    /**
     * Cancela o movimento gerado pela bean vinculada
     * @return bool
     */
    public function cancelarMovimento(): bool;
}
