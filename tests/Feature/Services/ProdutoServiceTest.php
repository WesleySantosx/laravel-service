<?php

namespace Tests\Feature\Services;

use App\Services\ProdutoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProdutoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_with_insufficient_margin_is_not_created(): void
    {
        $service = new ProdutoService;

        try {
            $service->create([
                'nome' => 'Produto inválido',
                'preco' => 109.99,
                'preco_custo' => 100,
            ]);

            $this->fail('A criação deveria rejeitar um preço abaixo da margem mínima.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'preco' => ['O preço de venda deve ser no mínimo 10% maior que o preço de custo. Preço mínimo: R$ 110.00.'],
            ], $exception->errors());
        }

        $this->assertDatabaseCount('produtos', 0);
    }

    public function test_product_is_updated_at_the_minimum_margin(): void
    {
        $service = new ProdutoService;
        $produto = $service->create([
            'nome' => 'Produto original',
            'preco' => 110,
            'preco_custo' => 100,
            'quantidade' => 5,
        ]);

        $resultado = $service->update($produto, [
            'nome' => 'Produto atualizado',
            'preco' => 220,
            'preco_custo' => 200,
        ]);

        $this->assertSame($produto, $resultado);
        $this->assertDatabaseCount('produtos', 1);
        $this->assertDatabaseHas('produtos', [
            'id' => $resultado->id,
            'nome' => 'Produto atualizado',
            'preco' => 220,
            'preco_custo' => 200,
            'quantidade' => 5,
        ]);
    }

    public function test_product_is_not_changed_when_updated_cost_breaks_the_minimum_margin(): void
    {
        $service = new ProdutoService;
        $produto = $service->create([
            'nome' => 'Produto original',
            'preco' => 110,
            'preco_custo' => 100,
        ]);
        $atributosOriginais = $produto->getAttributes();

        try {
            $service->update($produto, [
                'nome' => 'Produto inválido',
                'preco_custo' => 120,
            ]);

            $this->fail('A atualização deveria rejeitar um custo que viola a margem mínima.');
        } catch (ValidationException $exception) {
            $this->assertSame([
                'preco' => ['O preço de venda deve ser no mínimo 10% maior que o preço de custo. Preço mínimo: R$ 132.00.'],
            ], $exception->errors());
        }

        $this->assertSame($atributosOriginais, $produto->getAttributes());
        $this->assertSame($atributosOriginais, $produto->fresh()->getAttributes());
    }
}
