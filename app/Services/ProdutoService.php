<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Validation\ValidationException;

class ProdutoService
{
    public function create(array $dados): Produto
    {
        $this->verificarMargem($dados['preco'], $dados['preco_custo']);

        return Produto::create($dados);
    }

    public function update(Produto $produto, array $dados): Produto
    {
        $preco = (float) ($dados['preco'] ?? $produto->preco);
        $custo = (float) ($dados['preco_custo'] ?? $produto->preco_custo);
        $this->verificarMargem($preco, $custo);

        $produto->update($dados);

        return $produto;
    }

    private function verificarMargem(float $preco, float $custo): void
    {
        $precoMinimo = round($custo * 1.10, 2);

        if ($preco < $precoMinimo) {
            throw ValidationException::withMessages([
                'preco' => sprintf(
                    'O preço de venda deve ser no mínimo 10%% maior que o preço de custo. Preço mínimo: R$ %.2f.',
                    $precoMinimo
                ),
            ]);
        }
    }
}
