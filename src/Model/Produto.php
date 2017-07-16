<?php

namespace Model;

use RedBeanPHP\R as R;
use Respect\Validation\Validator as V;

/**
 * Description of Produto
 *
 * @author gusta
 */
class Produto extends AbstractModel implements 
    \ModelInterface\Validavel, 
    \ModelInterface\Movimenta {
    
    const UNIDADE_MEDIDA = [
        "UN" => "Unidades",
        "PC" => "Pacotes",
        "LT" => "Litros",
        "ML" => "Mililitros",
        "KG" => "Quilogramas",
        "GR" => "Gramas"
    ];

    public function populate(
        \Slim\Http\Request $request, 
        \RedBeanPHP\OODBBean $company = null, 
        \RedBeanPHP\OODBBean $user = null
    ) {
        
        $properties = parent::populate($request);
        
        if($company) {
            $this->bean->empresa = $company;
        }
        
        if($user) {
            $this->bean->usuario = $user;
        }
        
        foreach($properties as $property => $value) {
            switch($property){
                case 'categoria':
                    $this->bean->categoria = R::load('categoria', $value);
                    break;
                case 'fornecedor':
                    $this->bean->sharedFornecedorList = $this->linkSuppliers($value);
                    break;
                case 'valor':
//                    $value = str_replace('.', '', $value);
//                    $value = str_replace(',', '.', $value);
                    $this->bean->valor = $value;
                default:
                    $this->bean->{$property} = $value;
            }
        }
    }
    
    
    protected function linkSuppliers(array $suppliers): array {
        
        $sharedList = [];
        
        foreach($suppliers as $supplier) {
            if(is_numeric($supplier)) {
            $sharedList[] = R::load('fornecedor', $supplier);
            }
        }
        
        return $sharedList;
    }

    public static function isValid(array $formData): array {
        $errors = [];
        
        if(!V::notEmpty()->validate($formData["nome"])) {
            $errors["nome"] = "Favor informar o nome do produto";
        }
        
        if(!V::floatVal()->validate($formData["valor"])) {
            $errors["valor"] = "Favor informar um valor válido para o produto";
        }
        
        if(isset($formData["fornecedor"])) {
            if(!V::notEmpty()->validate($formData["fornecedor"])) {
                $errors["fornecedor"] = "Favor selecionar pelo menos um fornecedor para o produto";
            }
        } else {
            $errors["fornecedor"] = "Favor selecionar pelo menos um fornecedor para o produto";
        }
        
        if(!V::notEmpty()->validate($formData["unidadeMedida"])) {
            $errors["unidadeMedida"] = "Favor selecionar a unidade de medida do produto";
        }
        
        if(!V::notEmpty()->validate($formData["categoria"])) {
            $errors["categoria"] = "Favor selecionar a categoria do produto";
        }
        
        return $errors;
    }

    public function cancelarMovimento(): bool {
        
    }

    public function gerarMovimentacao(
        int $quantity = 0, 
        float $value = 0,
        string $type = 'Produto', 
        string $direction = 'S'
    ): Movimento {
        
        $movement = R::dispense('movimento');
        
        $movement->valor = $value * $quantity;
        $movement->tipoMovimento = TipoMovimento::enumFactory($type);
        $movement->direcao = $direction;
        $movement->empresa = $this->bean->empresa;
        $movement->data = new \DateTime;
        
        return $movement->box();
    }
    
    /**
     * Atualiza o estoque dos produtos recebidos
     * @param array $data
     * @param \RedBeanPHP\OODBBean $company
     * @param \RedBeanPHP\OODBBean $user
     * @return array
     */
    public function receive(array $data): array {
        
        R::begin();
        
        try {
            
            foreach($data["produtos"] as $receiptData) {
                $product = R::load('produto', $receiptData['produto']);
                $product->updateStock(
                    $product->sharedFornecedorList[$receiptData["fornecedor"]], 
                    $receiptData["quantidade"],
                    $receiptData["valor"]
                );
                R::store($product);
            }
            
        } catch (\RedBeanPHP\RedException $ex) {
            R::rollback();
            return ['error', $ex->getMessage()];
        }
        
        R::commit();
        return ['success', 'Estoque dos produtos foi atualizado com sucesso!'];
    }
    
    /**
     * Atualiza o estoque do produto
     * @param int $supplierId
     * @param float $quantity
     */
    public function updateStock(\RedBeanPHP\OODBBean $supplier, float $quantity = 0.0, float $value = 0.0) {
        
        $stock = R::dispense('estoque');
        
        $stock->quantidade = $quantity;
        $stock->fornecedor = $supplier;
        $stock->direcao = Estoque::ENTRADA;
        $stock->movimento = $this->gerarMovimentacao($quantity, $value);
        
        $this->bean->xownEstoqueList[] = $stock;
    }
    
    /**
     * Verifica o estoque do produto
     * @param array $data
     * @return string|json
     */
    public function checkProductStock(array $data): string {
        
        $product = R::load('produto', $data["id"]);
        $inStock = 0.0;
        
        foreach($product->ownEstoqueList as $stock) {
            if($stock->direcao == Estoque::ENTRADA) {
                $inStock += $stock->quantidade;
            } else {
                $inStock -= $stock->quantidade;
            }
        }
        
        $result = [
            'product' => [
                'id' => $product->id,
                'nome' => $product->nome,
                'valor' => $product->valor,
                'unidadeMedida' => $product->unidadeMedida,
                'quantidade' => $data["quantidade"]
            ]
        ];
        
        if($inStock  < $data["quantidade"]) {
            $result['hasStock'] = false;
        } else {
            $result['hasStock'] = true;
        }
        $result['stock'] = $inStock;
        
        return json_encode($result);
    }

}
