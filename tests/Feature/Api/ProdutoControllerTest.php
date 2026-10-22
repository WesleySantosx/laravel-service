<?php

namespace Tests\Feature\Api;

use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_product_is_created(): void
    {
        $response = $this->postJson('/api/produtos', [
            'nome' => 'Produto teste',
            'descricao' => 'Descricao do produto',
            'preco' => 110,
            'preco_custo' => 100,
            'quantidade' => 5,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nome', 'Produto teste');

        $this->assertDatabaseHas('produtos', [
            'nome' => 'Produto teste',
            'preco' => 110,
            'preco_custo' => 100,
        ]);
    }

    public function test_product_with_insufficient_margin_is_not_created(): void
    {
        $response = $this->postJson('/api/produtos', [
            'nome' => 'Produto teste',
            'preco' => 109.99,
            'preco_custo' => 100,
        ]);

        $response->assertUnprocessable()
            ->assertInvalid([
                'preco' => 'O preço de venda deve ser no mínimo 10% maior que o preço de custo. Preço mínimo: R$ 110.00.',
            ]);

        $this->assertDatabaseCount('produtos', 0);
    }

    public function test_valid_product_update_is_persisted(): void
    {
        $produto = Produto::create([
            'nome' => 'Produto teste',
            'preco' => 110,
            'preco_custo' => 100,
            'quantidade' => 5,
        ]);

        $response = $this->patchJson("/api/produtos/{$produto->id}", [
            'preco' => 120,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.preco', 120);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'preco' => 120,
            'preco_custo' => 100,
        ]);
    }

    public function test_product_update_cannot_break_the_minimum_margin(): void
    {
        $produto = Produto::create([
            'nome' => 'Produto teste',
            'preco' => 110,
            'preco_custo' => 100,
            'quantidade' => 5,
        ]);

        $response = $this->patchJson("/api/produtos/{$produto->id}", [
            'preco_custo' => 120,
        ]);

        $response->assertUnprocessable()
            ->assertInvalid([
                'preco' => 'O preço de venda deve ser no mínimo 10% maior que o preço de custo. Preço mínimo: R$ 132.00.',
            ]);

        $this->assertDatabaseHas('produtos', [
            'id' => $produto->id,
            'preco' => 110,
            'preco_custo' => 100,
        ]);
    }

    public function test_product_can_be_deleted(): void
    {
        $produto = Produto::create([
            'nome' => 'Produto teste',
            'preco' => 110,
            'preco_custo' => 100,
            'quantidade' => 5,
        ]);

        $response = $this->deleteJson("/api/produtos/{$produto->id}");

        $response->assertNoContent();
        $this->assertModelMissing($produto);
    }
}
