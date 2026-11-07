<?php

namespace Tests\Feature\Condominium;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Condominium\CondominiumDocument;
use App\Models\Condominium\CondominiumDocumentCategory;
use App\Models\Property\Property;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Documentos do condomínio (módulo 8): o administrador carrega (R2), corrige e apaga;
 * os residentes do prédio listam pela casa e pedem um link temporário.
 * Usa o CondominiumSeeder ("Edifício Brito Capelo", sem documentos) e o R2 falso.
 */
class CondominiumDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private User $admin;
    private User $morador;
    private User $residente;
    private Condominium $condominio;
    private string $casa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('r2');

        $this->admin      = User::where('email', 'condominio@example.com')->firstOrFail();
        $this->morador    = User::where('email', 'morador@example.com')->firstOrFail();
        $this->residente  = User::where('email', 'residente@example.com')->firstOrFail();
        $this->condominio = Condominium::where('name', 'Edifício Brito Capelo')->firstOrFail();
        $this->casa       = $this->condominio->properties()->value('id');
    }

    // ---------------------------------------------------------------- helpers

    private function categoria(string $nome): int
    {
        return CondominiumDocumentCategory::where('name', $nome)->value('id');
    }

    private function documento(string $categoria, array $dados = []): CondominiumDocument
    {
        return CondominiumDocument::factory()->create($dados + [
            'condominium_id'                   => $this->condominio->id,
            'condominium_document_category_id' => $this->categoria($categoria),
        ]);
    }

    private function carregar(User $user, array $dados = [])
    {
        return $this->actingAs($user)->post("/api/v1/condominiums/{$this->condominio->id}/documents", $dados + [
            'file'                             => UploadedFile::fake()->create('ata-marco.pdf', 200, 'application/pdf'),
            'title'                            => 'Ata da assembleia de março',
            'description'                      => 'Aprovação do orçamento.',
            'document_date'                    => '2026-03-15',
            'condominium_document_category_id' => $this->categoria('Atas de assembleia'),
        ], ['Accept' => 'application/json']);
    }

    // -------------------------------------------------------------- carregar

    public function test_o_administrador_carrega_um_documento_para_o_r2(): void
    {
        $id = $this->carregar($this->admin)
            ->assertCreated()
            ->assertJsonPath('data.title', 'Ata da assembleia de março')
            ->assertJsonPath('data.document_date', '2026-03-15')
            ->assertJsonPath('data.category.name', 'Atas de assembleia')
            ->assertJsonPath('data.file.name', 'ata-marco.pdf')
            ->assertJsonPath('data.file.mime_type', 'application/pdf')
            ->assertJsonMissingPath('data.file.url')
            ->json('data.id');

        $ficheiro = CondominiumDocument::find($id)->file;
        Storage::disk('r2')->assertExists($ficheiro->path);
        $this->assertSame($this->admin->id, $ficheiro->user_id);
    }

    public function test_carregar_valida_o_ficheiro_a_data_e_a_categoria(): void
    {
        $this->carregar($this->admin, [
            'file'                             => UploadedFile::fake()->create('notas.txt', 10, 'text/plain'),
            'document_date'                    => now()->addDay()->toDateString(),
            'condominium_document_category_id' => 99,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'file'                             => 'O documento tem de ser PDF, JPG ou PNG.',
                'document_date'                    => 'A data do documento não pode ser no futuro.',
                'condominium_document_category_id' => 'A categoria indicada não existe.',
            ]);

        $this->carregar($this->admin, ['file' => UploadedFile::fake()->create('grande.pdf', 16000, 'application/pdf')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file' => 'O documento não pode ter mais de 15 MB.']);

        $this->assertSame(0, $this->condominio->documents()->count());
    }

    public function test_so_o_administrador_do_condominio_carrega(): void
    {
        $outro = User::factory()->create();
        CondominiumAdmin::factory()->create(['user_id' => $outro->id]);
        $outro->assignRole('condominium_admin');

        $this->carregar($this->morador)->assertForbidden();
        $this->carregar($outro)->assertForbidden();
    }

    // --------------------------------------------------------------- listar

    public function test_o_administrador_lista_com_as_categorias_e_filtra(): void
    {
        $this->documento('Atas de assembleia', ['document_date' => '2025-01-10']);
        $this->documento('Atas de assembleia', ['document_date' => '2026-02-01']);
        $this->documento('Regulamento interno');

        $url = "/api/v1/condominiums/{$this->condominio->id}/documents";

        $this->actingAs($this->admin)->getJson($url)
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(4, 'categories');

        // só as atas, da mais recente para a mais antiga
        $atas = $this->actingAs($this->admin)
            ->getJson("{$url}?category_id={$this->categoria('Atas de assembleia')}")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->json('data');
        $this->assertSame(['2026-02-01', '2025-01-10'], array_column($atas, 'document_date'));

        $this->actingAs($this->admin)->getJson("{$url}?category_id=99")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id' => 'A categoria indicada não existe.']);
    }

    public function test_o_residente_lista_pela_casa_e_nao_pela_lista_do_administrador(): void
    {
        $this->documento('Regulamento interno');

        $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$this->casa}/condominium-documents")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(4, 'categories');

        $this->actingAs($this->morador)
            ->getJson("/api/v1/condominiums/{$this->condominio->id}/documents")
            ->assertForbidden();
    }

    public function test_casa_sem_condominio_devolve_uma_pagina_vazia_com_o_mesmo_formato(): void
    {
        $outraCasa = Property::whereNull('condominium_id')
            ->whereHas('contracts', fn ($q) => $q->where('resident_id', $this->morador->resident->id)->where('is_active', true))
            ->value('id');

        $this->actingAs($this->morador)
            ->getJson("/api/v1/properties/{$outraCasa}/condominium-documents")
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonStructure(['links' => ['first', 'last'], 'categories']);
    }

    public function test_ninguem_lista_os_documentos_da_casa_de_outra_pessoa(): void
    {
        $this->actingAs($this->residente)
            ->getJson("/api/v1/properties/{$this->casa}/condominium-documents")
            ->assertForbidden();
    }

    // ------------------------------------------------------------- download

    public function test_administrador_e_residentes_do_predio_recebem_um_link_temporario(): void
    {
        $url = "/api/v1/condominium-documents/{$this->documento('Outros')->id}/download";

        foreach ([$this->admin, $this->morador] as $user) {
            $this->actingAs($user)->getJson($url)
                ->assertOk()
                ->assertJsonStructure(['data' => ['url', 'expires_at']]);
        }

        $this->actingAs($this->residente)->getJson($url)->assertForbidden();
    }

    // ---------------------------------------------------------- editar/apagar

    public function test_o_administrador_corrige_os_dados_sem_trocar_o_ficheiro(): void
    {
        $documento = $this->documento('Atas de assembleia');
        $url = "/api/v1/condominium-documents/{$documento->id}";

        $this->actingAs($this->admin)
            ->patchJson($url, ['title' => 'Ata corrigida', 'condominium_document_category_id' => $this->categoria('Outros')])
            ->assertOk()
            ->assertJsonPath('data.title', 'Ata corrigida')
            ->assertJsonPath('data.category.name', 'Outros');

        $this->assertSame($documento->file_id, $documento->fresh()->file_id);

        $this->actingAs($this->admin)
            ->patchJson($url, ['document_date' => now()->addDay()->toDateString()])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['document_date']);
    }

    public function test_apagar_remove_o_documento_e_o_ficheiro(): void
    {
        $id = $this->carregar($this->admin)->json('data.id');
        $documento = CondominiumDocument::find($id);
        $ficheiro = $documento->file;

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/condominium-documents/{$id}")
            ->assertOk()
            ->assertJsonPath('message', 'Documento removido.');

        Storage::disk('r2')->assertMissing($ficheiro->path);
        $this->assertModelMissing($ficheiro);
        $this->assertModelMissing($documento);
    }

    public function test_o_residente_nao_edita_nem_apaga(): void
    {
        $url = "/api/v1/condominium-documents/{$this->documento('Outros')->id}";

        $this->actingAs($this->morador)->patchJson($url, ['title' => 'X'])->assertForbidden();
        $this->actingAs($this->morador)->deleteJson($url)->assertForbidden();
    }
}
