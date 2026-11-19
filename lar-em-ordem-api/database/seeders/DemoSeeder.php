<?php

namespace Database\Seeders;

use App\Events\Vault\VaultNotificationBroadcast;
use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Condominium\CondominiumDocumentCategory;
use App\Models\Condominium\CondominiumNotice;
use App\Models\File\File;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\Property\PropertyContract;
use App\Models\Property\PropertyType;
use App\Models\Property\PropertyTypology;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\ProblemCategory;
use App\Models\ServiceProvider\Proposal;
use App\Models\ServiceProvider\ServiceSpecialty;
use App\Models\ServiceProvider\ServiceZone;
use App\Models\User\Resident;
use App\Models\User\ResidentType;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\User;
use App\Models\Vault\Document;
use App\Models\Vault\DocumentCategory;
use App\Services\Vault\DocumentExpiryService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Dados para a apresentação, por cima do seed base:
 * - vizinhos do morador no Edifício Brito Capelo e residentes no Porto, em Matosinhos e em Gaia;
 * - mais prestadores, um por área, para todas as categorias terem quem responda;
 * - pedidos de assistência em todos os estados, com propostas pendentes, expiradas, aceites, recusadas e retiradas;
 * - documentos do Cofre com alertas de validade, um segundo condomínio, mais avisos e documentos do condomínio.
 * Os documentos só têm o registo (sem ficheiro no R2): abri-los responde que o ficheiro já não existe.
 * Precisa do CondominiumSeeder; fica fora dos testes, que contam os registos do seed base.
 */
class DemoSeeder extends Seeder
{
    private const MATOSINHOS = ['district' => 'Porto', 'county' => 'Matosinhos', 'location' => 'Matosinhos'];

    /** @var array<string, ServiceProviderModel> */
    private array $providers = [];

    public function run(DocumentExpiryService $expiry): void
    {
        $this->providers();

        $brito = Condominium::where('invite_code', 'CAPELA23')->firstOrFail();
        $parque = $this->secondCondominium($brito->admin->user);

        $homes = $this->residents($brito, $parque);

        $this->assistanceRequests($homes);
        $this->notices($brito, $parque, $homes);
        $this->condominiumDocuments($brito);
        $this->vaultDocuments($homes['morador']);

        // the daily job creates the expiry alerts; no broadcast while seeding
        Event::fakeFor(fn () => $expiry->checkAllDocuments(), [VaultNotificationBroadcast::class]);
    }

    // ------------------------------------------------------------------ prestadores

    private function providers(): void
    {
        $this->providers['servicos'] = User::where('email', 'prestador@example.com')->firstOrFail()->service_provider;
        $this->providers['servicinhos'] = User::where('email', 'residente@example.com')->firstOrFail()->service_provider;

        // the three providers of the ServiceProviderSeeder (water leaks, sockets and damp, in Matosinhos)
        ServiceProviderModel::whereKeyNot([$this->providers['servicos']->id, $this->providers['servicinhos']->id])
            ->orderBy('id')
            ->get()
            ->values()
            ->each(fn (ServiceProviderModel $provider, int $i) => $this->providers["outro{$i}"] = $provider);

        $grandePorto = ['Matosinhos', 'Porto', 'Maia', 'Vila Nova de Gaia'];

        $new = [
            'climatiza'   => ['Climatização Atlântico', 'Instalação e manutenção de ar condicionado, caldeiras e bombas de calor.', ['Ar condicionado', 'Aquecimento central'], $grandePorto],
            'carpintaria' => ['Carpintaria Leça', 'Portas, janelas, roupeiros e móveis por medida.', ['Portas e janelas', 'Móveis por medida'], ['Matosinhos', 'Porto']],
            'eletro'      => ['Eletrodomésticos Express', 'Reparação de máquinas de lavar, frigoríficos e arcas ao domicílio.', ['Máquinas de lavar', 'Frio'], $grandePorto],
            'limpezas'    => ['Brilho Norte Limpezas', 'Limpezas domésticas, de mudança e pós-obra.', ['Limpeza doméstica', 'Limpeza pós-obra'], ['Matosinhos', 'Porto', 'Maia']],
            'pinturas'    => ['Pinturas Matosinhos', 'Pintura interior e exterior e tratamento de humidades.', ['Pintura interior', 'Pintura exterior', 'Tratamento de humidades'], ['Matosinhos', 'Porto']],
            'jardins'     => ['Jardins da Foz', 'Manutenção de jardins, podas e sistemas de rega.', ['Manutenção de jardins', 'Rega automática'], ['Matosinhos', 'Porto']],
        ];

        foreach ($new as $key => [$company, $description, $specialties, $counties]) {
            $provider = ServiceProviderModel::factory()->create([
                'company_name' => $company,
                'description'  => $description,
            ]);
            $provider->user->assignRole('service_provider');
            $provider->zones()->sync(ServiceZone::where('district', 'Porto')->whereIn('county', $counties)->pluck('id'));

            ServiceSpecialty::whereIn('name', $specialties)->get()->each(
                fn (ServiceSpecialty $specialty) => $provider->specialties()->create([
                    'problem_category_id'  => $specialty->problem_category_id,
                    'service_specialty_id' => $specialty->id,
                ])
            );

            $this->providers[$key] = $provider;
        }
    }

    // ------------------------------------------------------------------ residentes e casas

    /**
     * @return array<string, Property> casas por chave, com o morador em "morador"
     */
    private function residents(Condominium $brito, Condominium $parque): array
    {
        $morador = User::where('email', 'morador@example.com')->firstOrFail()->resident;
        $homes = [
            'morador' => $brito->properties()->whereHas('contracts', fn ($q) => $q->where('resident_id', $morador->id))->firstOrFail(),
        ];

        $brito153 = ['street' => 'Rua Brito Capelo', 'postal_code' => '4450-073', 'door' => 'Nº 153'] + self::MATOSINHOS;
        $parque1200 = ['street' => 'Rua de Costa Cabral', 'postal_code' => '4200-211', 'door' => 'Nº 1200', 'district' => 'Porto', 'county' => 'Porto', 'location' => 'Paranhos'];

        $people = [
            // vizinhos do morador no Edifício Brito Capelo
            'ana'     => ['Ana Ferreira', 'ana.ferreira', $brito153 + ['fraction' => '1º Esq'], 'T3', 112, 'Owner', $brito],
            'rui'     => ['Rui Carvalho', 'rui.carvalho', $brito153 + ['fraction' => '1º Dto'], 'T2', 86, 'Tenant', $brito],
            'marta'   => ['Marta Sousa', 'marta.sousa', $brito153 + ['fraction' => '3º Esq'], 'T2', 90, 'Owner', $brito],
            'joaquim' => ['Joaquim Pinto', 'joaquim.pinto', $brito153 + ['fraction' => 'R/C Dto'], 'T1', 64, 'Owner', $brito],
            'ines'    => ['Inês Moreira', 'ines.moreira', $brito153 + ['fraction' => '3º Dto'], 'T2', 88, 'Tenant', $brito],
            // Residências do Parque (Paranhos), o segundo prédio do administrador
            'pedro'   => ['Pedro Almeida', 'pedro.almeida', $parque1200 + ['fraction' => '4º B'], 'T3', 120, 'Owner', $parque],
            'helena'  => ['Helena Castro', 'helena.castro', $parque1200 + ['fraction' => '2º A'], 'T2', 95, 'Tenant', $parque],
            // fora de condomínios
            'tiago'   => ['Tiago Lopes', 'tiago.lopes', ['street' => 'Rua Roberto Ivens', 'postal_code' => '4450-249', 'door' => 'Nº 640', 'fraction' => '2º Frt'] + self::MATOSINHOS, 'T2', 78, 'Tenant', null],
            'luis'    => ['Luís Teixeira', 'luis.teixeira', ['street' => 'Rua de Vila Franca', 'postal_code' => '4460-302', 'door' => 'Nº 85', 'fraction' => null, 'district' => 'Porto', 'county' => 'Matosinhos', 'location' => 'Senhora da Hora'], 'T4', 180, 'Owner', null, 'House'],
            'carla'   => ['Carla Mendes', 'carla.mendes', ['street' => 'Rua de Cedofeita', 'postal_code' => '4050-180', 'door' => 'Nº 312', 'fraction' => '1º', 'district' => 'Porto', 'county' => 'Porto', 'location' => 'Cedofeita, Santo Ildefonso, Sé, Miragaia, São Nicolau e Vitória'], 'T1', 58, 'Tenant', null],
            'sofia'   => ['Sofia Rocha', 'sofia.rocha', ['street' => 'Avenida da República', 'postal_code' => '4430-190', 'door' => 'Nº 1350', 'fraction' => '6º Esq', 'district' => 'Porto', 'county' => 'Vila Nova de Gaia', 'location' => 'Mafamude'], 'T3', 105, 'Owner', null],
        ];

        foreach ($people as $key => $person) {
            [$name, $login, $address, $typology, $area, $residentType, $condominium] = $person;

            $user = User::factory()->create(['name' => $name, 'email' => "{$login}@example.com"]);
            $user->assignRole('resident');
            $resident = Resident::factory()->create(['user_id' => $user->id]);

            $homes[$key] = $this->home($resident, $address, $typology, $area, $residentType, $condominium, $person[7] ?? 'Apartment');
        }

        $contracts = User::where('email', 'residente@example.com')->firstOrFail()
            ->resident->contracts()
            ->where('is_active', true)
            ->with(['residentType', 'property.address'])
            ->orderBy('id')
            ->get();

        $homes['residente'] = $contracts->firstWhere('residentType.type', 'Owner')->property;
        $homes['residente_arrendada'] = $contracts->firstWhere('residentType.type', 'Tenant')->property;

        $homes['residente']->address->update(['street' => 'Rua de Santa Catarina', 'postal_code' => '4000-447', 'door' => 'Nº 412', 'fraction' => '3º Esq', 'district' => 'Porto', 'county' => 'Porto', 'location' => 'Bonfim']);
        $homes['residente_arrendada']->address->update(['street' => 'Rua Soares dos Reis', 'postal_code' => '4400-315', 'door' => 'Nº 215', 'fraction' => '1º Dto', 'district' => 'Porto', 'county' => 'Vila Nova de Gaia', 'location' => 'Mafamude']);

        return $homes;
    }

    private function home(Resident $resident, array $address, string $typology, int $area, string $residentType, ?Condominium $condominium, string $type): Property
    {
        $property = Property::factory()->create([
            'address_id'           => Address::create($address)->id,
            'property_type_id'     => PropertyType::where('type', $type)->value('id'),
            'property_typology_id' => PropertyTypology::where('typology', $typology)->value('id'),
            'area'                 => $area,
            'condominium_id'       => $condominium?->id,
        ]);

        $tenant = $residentType === 'Tenant';

        PropertyContract::factory()->create([
            'resident_id'      => $resident->id,
            'property_id'      => $property->id,
            'resident_type_id' => ResidentType::where('type', $residentType)->value('id'),
            'start_date'       => now()->subMonths(fake()->numberBetween($tenant ? 6 : 24, $tenant ? 30 : 120))->toDateString(),
            'end_date'         => $tenant ? now()->addMonths(fake()->numberBetween(6, 30))->toDateString() : null,
            'is_active'        => true,
        ]);

        return $property;
    }

    private function secondCondominium(User $admin): Condominium
    {
        return Condominium::factory()->create([
            'condominium_admin_id' => $admin->condominium_admin->id,
            'address_id'           => Address::create([
                'street'      => 'Rua de Costa Cabral',
                'postal_code' => '4200-211',
                'door'        => 'Nº 1200',
                'district'    => 'Porto',
                'county'      => 'Porto',
                'location'    => 'Paranhos',
            ])->id,
            'name'                 => 'Residências do Parque',
            'reserve_fund'         => 8300,
            'invite_code'          => 'PARQUE26',
        ]);
    }

    // ------------------------------------------------------------------ pedidos de assistência

    /**
     * @param array<string, Property> $homes
     */
    private function assistanceRequests(array $homes): void
    {
        // [casa, categoria, título, descrição, estado, há quantos dias foi aberto, propostas: [prestador, valor, estado]]
        // estados das propostas: pending, expired (pendente fora da validade), accepted, upcoming (aceite, trabalho
        // ainda por fazer), rejected e withdrawn
        $requests = [
            ['morador', 'Climatização', 'Ar condicionado do quarto não arrefece', 'Liga e faz barulho, mas o ar sai à temperatura ambiente. Tem uns seis anos.', 'open', 4, [
                ['climatiza', 85, 'pending'],
            ]],
            ['morador', 'Eletrodomésticos', 'Máquina de lavar roupa não centrifuga', 'Lava normalmente, mas no fim a roupa sai encharcada. Não dá nenhum erro no visor.', 'closed', 45, [
                ['eletro', 75, 'accepted'],
            ]],
            ['morador', 'Carpintaria', 'Porta da varanda não fecha bem', 'Com a humidade a porta incha e é preciso fazer força para a fechar.', 'open', 1, []],

            ['ana', 'Canalização', 'Autoclismo sempre a correr', 'A água não para de correr para a sanita, mesmo depois de encher o depósito.', 'open', 6, [
                ['servicos', 65, 'rejected'],
                ['outro0', 40, 'pending'],
            ]],
            ['ana', 'Pintura', 'Pintar a sala e o corredor', 'Cerca de 40 m² de paredes, cor branca. A casa fica livre durante o dia.', 'closed', 70, [
                ['pinturas', 650, 'accepted'],
                ['outro1', 820, 'rejected'],
            ]],

            ['rui', 'Eletricidade', 'Disjuntor da cozinha dispara com o forno', 'Sempre que o forno e a placa estão ligados ao mesmo tempo o disjuntor vai abaixo.', 'open', 12, [
                ['servicinhos', 90, 'pending'],
                ['servicos', 70, 'expired'],
                ['outro2', 120, 'withdrawn'],
            ]],
            ['rui', 'Limpezas', 'Limpeza da casa antes da mudança', 'Apartamento T2 vazio, inclui cozinha, casa de banho e janelas.', 'cancelled', 25, [
                ['limpezas', 120, 'rejected'],
            ]],

            ['marta', 'Canalização', 'Esquentador a gás com chama amarela', 'A chama ficou amarela e o esquentador às vezes apaga-se a meio do banho.', 'closed', 9, [
                ['servicos', 95, 'upcoming'],
                ['outro0', 110, 'rejected'],
            ]],
            ['marta', 'Climatização', 'Instalar ar condicionado na sala', 'Sala com 30 m², a unidade exterior pode ficar na varanda.', 'open', 3, [
                ['climatiza', 1150, 'pending'],
            ]],

            ['joaquim', 'Canalização', 'Torneira da banheira a pingar', 'Pinga dia e noite, mesmo bem fechada. Já mudei o vedante e não resolveu.', 'open', 2, []],
            ['joaquim', 'Eletricidade', 'Certificação da instalação elétrica', 'Preciso do certificado para mudar o contrato da luz para o meu nome.', 'closed', 95, [
                ['servicinhos', 180, 'accepted'],
                ['servicos', 210, 'rejected'],
            ]],

            ['ines', 'Eletrodomésticos', 'Frigorífico faz muito barulho', 'Faz um zumbido alto de noite e a parte de baixo tem gelo acumulado.', 'open', 5, [
                ['eletro', 60, 'pending'],
            ]],
            ['ines', 'Pintura', 'Bolor no teto da casa de banho', 'A casa de banho não tem janela e o bolor volta sempre, mesmo depois de limpar.', 'open', 8, [
                ['pinturas', 180, 'pending'],
                ['outro1', 210, 'pending'],
            ]],

            ['tiago', 'Canalização', 'Cano rebentado na garagem', 'Há água a sair da parede da garagem, junto ao contador. Fechei a torneira geral.', 'open', 1, [
                ['servicos', 150, 'pending'],
                ['outro2', 130, 'pending'],
            ]],
            ['tiago', 'Eletricidade', 'Instalar candeeiros na sala', 'Três pontos de luz no teto, os candeeiros já estão comprados.', 'closed', 35, [
                ['servicos', 120, 'accepted'],
                ['servicinhos', 140, 'rejected'],
            ]],

            ['luis', 'Jardinagem', 'Sistema de rega não liga', 'O programador está ligado mas nenhuma das zonas de rega abre.', 'open', 7, [
                ['jardins', 95, 'pending'],
            ]],
            ['luis', 'Canalização', 'Esgoto do quintal entupido', 'A caixa de esgoto do quintal está a transbordar quando chove.', 'open', 3, []],

            ['carla', 'Eletricidade', 'Tomadas da cozinha sem corrente', 'As tomadas da bancada deixaram de funcionar. O resto da casa tem luz.', 'open', 2, []],
            ['carla', 'Canalização', 'Substituir sanita antiga', 'A sanita tem uma racha na base e perde água. Quero substituir por uma suspensa.', 'closed', 55, [
                ['servicos', 260, 'accepted'],
            ]],

            ['pedro', 'Canalização', 'Pressão da água muito fraca no duche', 'Nas outras torneiras a pressão é normal, só no duche é que a água sai fraca.', 'open', 10, [
                ['servicos', 65, 'pending'],
            ]],
            ['pedro', 'Carpintaria', 'Montar roupeiro embutido', 'Roupeiro de 2,5 m no quarto principal, com portas de correr.', 'cancelled', 40, [
                ['carpintaria', 950, 'rejected'],
            ]],

            ['helena', 'Limpezas', 'Limpeza pós-obra da cozinha', 'Acabei de remodelar a cozinha, há pó de obra em toda a casa.', 'open', 4, [
                ['limpezas', 220, 'pending'],
            ]],
            ['residente', 'Eletrodomésticos', 'Máquina de lavar loiça não escoa', 'No fim do programa fica água parada no fundo e a loiça sai com resíduos.', 'open', 3, [
                ['eletro', 70, 'pending'],
            ]],
            ['residente', 'Pintura', 'Fissuras na parede da sala', 'Apareceram fissuras finas por cima da janela. Queria reparar e pintar a parede toda.', 'open', 6, [
                ['pinturas', 240, 'pending'],
            ]],
            ['residente', 'Canalização', 'Substituir torneira da cozinha', 'A torneira misturadora está a perder água pela base. Já comprei uma nova.', 'closed', 50, [
                ['servicos', 85, 'accepted'],
            ]],
            ['residente', 'Limpezas', 'Limpeza de vidros e estores', 'Limpeza por fora e por dentro de todas as janelas e estores.', 'cancelled', 30, [
                ['limpezas', 60, 'rejected'],
            ]],
            ['residente_arrendada', 'Climatização', 'Ar condicionado a pingar água', 'A unidade interior do quarto pinga para o chão quando está ligada.', 'open', 2, [
                ['climatiza', 65, 'pending'],
            ]],
            ['residente_arrendada', 'Eletrodomésticos', 'Arca congeladora não congela', 'A arca liga e faz barulho, mas a temperatura não desce dos 5 graus.', 'closed', 20, [
                ['eletro', 110, 'upcoming'],
            ]],
        ];

        foreach ($requests as [$home, $category, $title, $description, $status, $daysAgo, $proposals]) {
            $property = $homes[$home];
            $openedAt = now()->subDays($daysAgo)->setTime(fake()->numberBetween(8, 21), fake()->numberBetween(0, 59));
            $closedAt = $status === 'open' ? null : $openedAt->copy()->addDays(min(3, max(1, $daysAgo - 1)));

            $request = AssistanceRequest::factory()->create([
                'property_id'         => $property->id,
                'resident_id'         => $property->contracts()->where('is_active', true)->value('resident_id'),
                'problem_category_id' => ProblemCategory::where('name', $category)->value('id'),
                'title'               => $title,
                'description'         => $description,
                'status'              => $status,
                'created_at'          => $openedAt,
                'updated_at'          => $closedAt ?? $openedAt,
                'closed_at'           => $closedAt,
            ]);

            foreach ($proposals as [$provider, $value, $state]) {
                $this->proposal($request, $this->providers[$provider], $value, $state, $openedAt, $closedAt);
            }
        }
    }

    private function proposal(AssistanceRequest $request, ServiceProviderModel $provider, float $value, string $state, Carbon $openedAt, ?Carbon $closedAt): void
    {
        $sentAt = $openedAt->copy()->addHours(fake()->numberBetween(2, 30));
        $answeredAt = $closedAt ?? $sentAt->copy()->addDay();

        // the job date: past for finished work, ahead for pending proposals and upcoming work
        $availableDate = match ($state) {
            'accepted' => $answeredAt->copy()->addDays(fake()->numberBetween(2, 5)),
            'upcoming' => now()->addDays(3),
            'expired'  => now()->addDays(4),
            default    => $closedAt ?? now()->addDays(fake()->numberBetween(4, 12)),
        };
        $validity = match ($state) {
            'expired'  => now()->subDays(2),
            'pending'  => now()->addDays(fake()->numberBetween(1, 3)),
            default    => $sentAt->copy()->addDays(5),
        };

        $hour = fake()->numberBetween(8, 15);

        Proposal::factory()->create([
            'assistance_request_id' => $request->id,
            'service_provider_id'   => $provider->id,
            'estimated_value'       => $value,
            'validity'              => $validity->min($availableDate)->toDateString(),
            'available_date'        => $availableDate->toDateString(),
            'available_from'        => sprintf('%02d:00', $hour),
            'available_until'       => sprintf('%02d:00', $hour + fake()->randomElement([2, 3, 4])),
            'status'                => match ($state) {
                'accepted', 'upcoming' => 'accepted',
                'expired'              => 'pending',
                default                => $state,
            },
            'response_date'         => in_array($state, ['accepted', 'upcoming', 'rejected'], true) ? $answeredAt->toDateString() : null,
            'created_at'            => $sentAt,
            'updated_at'            => in_array($state, ['pending', 'expired'], true) ? $sentAt : $answeredAt,
        ]);
    }

    // ------------------------------------------------------------------ condomínios

    /**
     * @param array<string, Property> $homes
     */
    private function notices(Condominium $brito, Condominium $parque, array $homes): void
    {
        $morador = $homes['morador']->contracts()->where('is_active', true)->value('resident_id');

        $readersOf = fn (Condominium $condominium) => $condominium->properties()
            ->with(['contracts' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn (Property $property) => $property->contracts->first()?->resident_id)
            ->filter()
            ->values();

        $assembly = now()->addDays(12);

        $brito153 = [
            ['Assembleia geral de condóminos', "A assembleia geral ordinária realiza-se no dia {$assembly->format('d/m')}, às 21h, na sala do condomínio. Ordem de trabalhos: contas do ano, orçamento para o próximo ano e pintura da fachada.", 'high', 5, 12],
            ['Pagamento das quotas do trimestre', 'As quotas do trimestre devem ser pagas até ao fim do mês, por transferência para a conta do condomínio indicada na última ata.', 'normal', 18, 10],
            ['Desinfestação das garagens', 'Na próxima segunda-feira, a partir das 10h, a empresa contratada faz a desinfestação das garagens e da casa do lixo. Retirem os carros até essa hora, por favor.', 'normal', 2, 6],
            ['Inspeção periódica ao gás', 'A inspeção às instalações de gás das frações decorreu no dia 14. Quem não estava em casa deve marcar nova visita com a administração.', 'normal', 45, -20],
            ['Obras na cobertura concluídas', 'As obras de impermeabilização da cobertura terminaram. Obrigado pela paciência durante as últimas semanas.', 'normal', 80, -50],
        ];

        $parque1200 = [
            ['Substituição do intercomunicador', 'O intercomunicador da entrada vai ser substituído na quarta-feira, entre as 9h e as 17h. Durante esse período a porta fica aberta com vigilância.', 'high', 1, 4],
            ['Horário da recolha de recicláveis', 'A Câmara do Porto alterou a recolha de recicláveis para as terças e sextas-feiras. Coloquem os sacos no ecoponto na véspera à noite.', 'normal', 10, null],
        ];

        foreach ([[$brito, $brito153], [$parque, $parque1200]] as [$condominium, $notices]) {
            $readers = $readersOf($condominium);

            foreach ($notices as [$title, $message, $priority, $publishedDaysAgo, $expiresInDays]) {
                $notice = CondominiumNotice::factory()->create([
                    'condominium_id' => $condominium->id,
                    'title'          => $title,
                    'message'        => $message,
                    'priority'       => $priority,
                    'published_at'   => now()->subDays($publishedDaysAgo),
                    'expires_at'     => $expiresInDays === null ? null : now()->addDays($expiresInDays),
                ]);

                // older notices were read by more neighbours; the morador keeps the new ones unread for the demo
                $count = min($readers->count(), intdiv($publishedDaysAgo, 3) + 1);
                $readers->reject(fn (string $id) => $id === $morador && $publishedDaysAgo < 10)
                    ->shuffle()
                    ->take($count)
                    ->each(fn (string $id) => $notice->readers()->attach($id, [
                        'read_at' => now()->subDays(fake()->numberBetween(0, max(0, $publishedDaysAgo - 1))),
                    ]));
            }
        }
    }

    private function condominiumDocuments(Condominium $brito): void
    {
        $adminId = $brito->admin->user_id;
        $category = fn (string $name) => CondominiumDocumentCategory::where('name', $name)->value('id');

        // years follow the dates, so the documents keep making sense whenever the seed runs
        [$ata14, $ata15, $ata16] = [now()->subMonths(20), now()->subMonths(8), now()->subMonths(5)];

        $documents = [
            ['Atas de assembleia', 'Ata n.º 14 - Assembleia ordinária', 'Aprovação das contas de ' . ($ata14->year - 1) . " e do orçamento de {$ata14->year}.", $ata14],
            ['Atas de assembleia', 'Ata n.º 15 - Assembleia ordinária', 'Aprovação das contas de ' . ($ata15->year - 1) . ' e eleição da administração.', $ata15],
            ['Atas de assembleia', 'Ata n.º 16 - Assembleia extraordinária', 'Aprovação das obras de impermeabilização da cobertura.', $ata16],
            ['Regulamento interno', 'Regulamento interno do Edifício Brito Capelo', 'Utilização das garagens, da casa do lixo e das partes comuns.', now()->subYears(3)],
            ['Orçamento do fundo comum de reserva', "Orçamento e quotas para {$ata15->year}", 'Despesas previstas, valor das quotas por permilagem e fundo comum de reserva.', $ata15],
            ['Outros', 'Apólice do seguro multirriscos do prédio', 'Seguro das partes comuns, válido até ao fim do ano.', now()->subMonths(10)],
            ['Outros', 'Certificado de inspeção do elevador', 'Inspeção periódica realizada pela entidade inspetora.', now()->subMonths(4)],
        ];

        foreach ($documents as [$categoryName, $title, $description, $date]) {
            CondominiumDocument::factory()->create([
                'condominium_id'                   => $brito->id,
                'condominium_document_category_id' => $category($categoryName),
                'file_id'                          => $this->fileRecord($adminId, $title, 'condominium_documents')->id,
                'title'                            => $title,
                'description'                      => $description,
                'document_date'                    => $date->toDateString(),
            ]);
        }
    }

    // ------------------------------------------------------------------ cofre digital

    private function vaultDocuments(Property $moradorHome): void
    {
        $category = fn (string $name) => DocumentCategory::where('name', $name)->value('id');

        $residenteHome = User::where('email', 'residente@example.com')->firstOrFail()
            ->resident->contracts()
            ->where('is_active', true)
            ->whereHas('residentType', fn ($type) => $type->where('type', 'Owner'))
            ->orderBy('id')
            ->firstOrFail()
            ->property;
            
        $imi = now()->subMonths(7);

        // [categoria, nome, descrição, emitido, válido até]
        $documents = [
            $moradorHome->id => [
                ['Escritura', 'Escritura de compra e venda', 'Escritura pública da compra do 2º Esq.', now()->subYears(6)->subMonths(3), null],
                ['Caderneta predial', 'Caderneta predial urbana', 'Artigo matricial e valor patrimonial tributário atualizado.', now()->subMonths(2), null],
                ['Certidão permanente do registo predial', 'Certidão permanente', 'Código de acesso válido por seis meses.', now()->subMonths(5), now()->addDays(25)],
                ['Licença de utilização', 'Licença de utilização', 'Emitida pela Câmara Municipal de Matosinhos.', now()->subYears(22), null],
                ['Certificado energético', 'Certificado energético (classe B)', 'Válido para venda ou arrendamento.', now()->subYears(3), now()->addYears(7)],
                ['Comprovativos de IMI', 'IMI ' . ($imi->year - 1) . ' - 1.ª prestação', 'Pagamento da primeira prestação do IMI.', $imi, null],
                ['Contrato e mapa de amortizações', 'Contrato de crédito habitação', 'Contrato com o banco e plano de prestações até 2049.', now()->subYears(6)->subMonths(3), null],
                ['Declaração anual de juros', 'Declaração de juros de ' . (now()->year - 1), 'Para o IRS: juros pagos no crédito habitação.', now()->startOfYear()->addDays(20), null],
                ['Multirriscos habitação e seguro de vida', 'Seguro multirriscos habitação', 'Apólice anual, renovação automática se não for cancelada.', now()->subYear()->addDays(6), now()->addDays(6)],
                ['Multirriscos habitação e seguro de vida', 'Seguro de vida (apólice antiga)', 'Substituído pelo seguro de vida do novo banco.', now()->subYears(2)->subDays(12), now()->subDays(12)],
            ],
            $residenteHome->id => [
                ['Caderneta predial', 'Caderneta predial urbana', 'Artigo matricial atualizado.', now()->subMonths(4), null],
                ['Comprovativos de IMI', 'IMI ' . ($imi->year - 1) . ' - pagamento único', 'Pagamento do IMI numa só prestação.', $imi->copy()->addMonth(), null],
                ['Certificado energético', 'Certificado energético (classe C)', 'Pedido para o novo contrato de arrendamento.', now()->subYears(10)->addDays(18), now()->addDays(18)],
            ],
        ];

        foreach ($documents as $propertyId => $list) {
            foreach ($list as [$categoryName, $name, $description, $issued, $expires]) {
                Document::create([
                    'property_id'          => $propertyId,
                    'document_category_id' => $category($categoryName),
                    'name'                 => $name,
                    'description'          => $description,
                    'issue_date'           => $issued->toDateString(),
                    'expiration_date'      => $expires?->toDateString(),
                ]);
            }
        }
    }

    // ------------------------------------------------------------------ ficheiros

    /**
     * The file record only: nothing is uploaded to R2, so opening it answers that the file no longer exists.
     */
    private function fileRecord(string $userId, string $title, string $category): File
    {
        $id = (string) Str::ulid();

        return File::factory()->create([
            'user_id'       => $userId,
            'title'         => $title,
            'original_name' => Str::slug($title) . '.pdf',
            'path'          => "users/{$userId}/{$category}/{$id}.pdf",
            'mime_type'     => 'application/pdf',
            'size_in_bytes' => fake()->numberBetween(80_000, 900_000),
            'category'      => $category,
        ]);
    }
}