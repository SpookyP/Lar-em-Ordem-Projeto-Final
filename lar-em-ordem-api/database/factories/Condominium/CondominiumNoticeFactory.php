<?php

namespace Database\Factories\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumNotice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CondominiumNotice>
 */
class CondominiumNoticeFactory extends Factory
{
    /** [título, mensagem, prioridade] */
    private const CATALOG = [
        ['Corte de água', 'Por obras na rede pública, a água vai estar cortada na terça-feira, das 9h às 13h.', 'high'],
        ['Assembleia geral de condóminos', 'A assembleia geral realiza-se no dia 15, às 21h, na sala do condomínio. A ordem de trabalhos está afixada na entrada.', 'high'],
        ['Manutenção do elevador', 'O elevador vai estar parado para manutenção na sexta-feira de manhã.', 'normal'],
        ['Pagamento das quotas', 'Lembramos que as quotas do trimestre devem ser pagas até ao fim do mês.', 'normal'],
        ['Limpeza das escadas', 'A limpeza das escadas passa a ser feita às quintas-feiras de manhã.', 'normal'],
        ['Desinfestação da garagem', 'Na próxima segunda-feira é feita a desinfestação da garagem. Por favor, retire os objetos do chão.', 'low'],
        ['Portão da garagem reparado', 'O portão da garagem já foi reparado e voltou a funcionar normalmente.', 'low'],
    ];

    public function definition(): array
    {
        [$title, $message, $priority] = fake()->randomElement(self::CATALOG);

        return [
            'condominium_id' => Condominium::factory(),
            'title'          => $title,
            'message'        => $message,
            'priority'       => $priority,
            'published_at'   => fake()->dateTimeBetween('-7 days', 'now'),
            'expires_at'     => now()->addDays(fake()->numberBetween(7, 30)),
        ];
    }

    public function priority(string $priority): static
    {
        return $this->state(['priority' => $priority]);
    }

    /** Sem data de fim (fica em vigor até ser apagado). */
    public function permanent(): static
    {
        return $this->state(['expires_at' => null]);
    }

    /** Já terminou: só aparece no histórico do administrador. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'published_at' => now()->subDays(fake()->numberBetween(15, 60)),
            'expires_at'   => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }
}