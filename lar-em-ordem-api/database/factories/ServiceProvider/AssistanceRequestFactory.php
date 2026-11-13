<?php

namespace Database\Factories\ServiceProvider;

use App\Models\Property\Property;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProblemCategory;
use App\Models\User\Resident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssistanceRequest>
 */
class AssistanceRequestFactory extends Factory
{
        /** Pedidos realistas: [categoria (ProblemCategorySeeder), título, descrição]. */
    private const CATALOG = [
        ['Canalização', 'Torneira da cozinha a pingar', 'A torneira não fecha bem e pinga a noite toda.'],
        ['Canalização', 'Autoclismo sempre a correr', 'O autoclismo não para de encher e a conta da água subiu.'],
        ['Canalização', 'Lavatório entupido', 'A água do lavatório escoa muito devagar.'],
        ['Eletricidade', 'Tomada sem corrente', 'Uma das tomadas da sala deixou de funcionar.'],
        ['Eletricidade', 'Disjuntor dispara ao ligar o forno', 'O quadro elétrico desliga a cozinha sempre que ligo o forno.'],
        ['Climatização', 'Ar condicionado não arrefece', 'O aparelho liga mas só deita ar quente.'],
        ['Carpintaria', 'Porta do roupeiro descaída', 'A porta do roupeiro já não fecha bem.'],
        ['Pintura', 'Pintar as paredes da sala', 'As paredes da sala têm manchas e precisam de pintura.'],
        ['Eletrodomésticos', 'Máquina de lavar não centrifuga', 'A roupa sai encharcada no fim do programa.'],
        ['Limpezas', 'Limpeza depois de obras', 'Preciso de uma limpeza completa depois da remodelação da cozinha.'],
        ['Jardinagem', 'Cortar a sebe do jardim', 'A sebe tem cerca de 15 metros e precisa de corte.'],
    ];
    public function definition(): array
    {
        [$category, $title, $description] = fake()->randomElement(self::CATALOG);

        return [
            'property_id'         => Property::factory(),
            'resident_id'         => Resident::factory(),
            'problem_category_id' => fn () => ProblemCategory::firstOrCreate(['name' => $category], ['description' => $category])->id,
            'title'               => $title,
            'description'         => $description,
            'status'              => 'open',
            'closed_at'           => null,
        ];
    }
    
    /**
     * Pedido fechado (o residente aceitou uma proposta).
     */
    public function closed(): static
    {
        return $this->state(function () {
            $closedAt = now()->subDays(fake()->numberBetween(1, 30));

            return [
                'status'     => 'closed',
                'created_at' => $closedAt->copy()->subDays(fake()->numberBetween(1, 10)),
                'closed_at'  => $closedAt,
            ];
        });
    }

    /**
     * Pedido cancelado pelo residente.
     */
    public function cancelled(): static
    {
        return $this->state(function () {
            $closedAt = now()->subDays(fake()->numberBetween(1, 30));

            return [
                'status'     => 'cancelled',
                'created_at' => $closedAt->copy()->subDays(fake()->numberBetween(1, 10)),
                'closed_at'  => $closedAt,
            ];
        });
    }
}