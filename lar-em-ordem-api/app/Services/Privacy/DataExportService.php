<?php

namespace App\Services\Privacy;

use App\Models\File\File;
use App\Models\Invoice\Consumption;
use App\Models\Invoice\Invoice;
use App\Models\Property\Address;
use App\Models\Property\PropertyContract;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\Proposal;
use App\Models\User\Resident;
use App\Models\User\User;
use App\Models\Vault\Document;
use Carbon\CarbonInterface;

/**
 * A copy of everything the app keeps about a user, in one JSON file (RGPD art. 15 and 20: access and
 * portability). Keys and labels are in Portuguese because the file is for the person, not for the frontend.
 * Only the user's own data: other people appear at most by company name, never with their NIF, email or address.
 * Files are listed (name, type, size), not included; each one can still be opened in the app.
 */
class DataExportService
{
    private const RESIDENT_TYPES = ['Owner' => 'proprietário', 'Tenant' => 'arrendatário'];

    public function export(User $user): array
    {
        $user->loadMissing(['resident', 'service_provider', 'partner', 'condominium_admin', 'social_account']);

        return array_filter([
            'gerado_em' => now()->toIso8601String(),
            'conta' => [
                'nome' => $user->name,
                'email' => $user->email,
                'email_confirmado_em' => $this->moment($user->email_verified_at),
                'criada_em' => $this->moment($user->created_at),
                'entra_com' => $user->social_account?->provider ?? 'password',
            ],
            'consentimentos' => $user->consents()->oldest()->get()->map(fn ($consent) => [
                'tipo' => $consent->type,
                'aceite' => (bool) $consent->granted,
                'versao_politica' => $consent->policy_version,
                'origem' => $consent->source,
                'em' => $this->moment($consent->created_at),
            ])->all(),
            'residente' => $user->resident ? $this->resident($user->resident) : null,
            'prestador' => $user->service_provider ? $this->provider($user) : null,
            'parceiro' => $user->partner ? $this->partner($user) : null,
            'administrador_condominio' => $user->condominium_admin ? $this->condominiumAdmin($user) : null,
            'ficheiros' => File::where('user_id', $user->id)->oldest()->get()->map(fn (File $file) => [
                'titulo' => $file->title,
                'nome_original' => $file->original_name,
                'tipo' => $file->mime_type,
                'tamanho_bytes' => $file->size_in_bytes,
                'categoria' => $file->category,
                'enviado_em' => $this->moment($file->created_at),
            ])->all(),
        ], fn ($section) => $section !== null);
    }

    private function resident(Resident $resident): array
    {
        $contracts = $resident->contracts()
            ->active()
            ->with(['residentType', 'property.address', 'property.propertyType', 'property.propertyTypology'])
            ->get();

        // the vault belongs to the owner: documents only from the homes the resident owns
        $owned = $contracts->filter(fn (PropertyContract $contract) => $contract->residentType?->type === 'Owner')
            ->pluck('property_id');

        return [
            'nif' => $resident->nif,
            'casas' => $contracts->map(fn (PropertyContract $contract) => [
                'morada' => $this->address($contract->property->address),
                'tipo' => $contract->property->propertyType?->type,
                'tipologia' => $contract->property->propertyTypology?->typology,
                'area_m2' => $contract->property->area,
                'como' => self::RESIDENT_TYPES[$contract->residentType?->type] ?? $contract->residentType?->type,
                'desde' => $this->day($contract->start_date),
                'ate' => $this->day($contract->end_date),
            ])->values()->all(),
            'faturas' => Invoice::where('resident_id', $resident->id)
                ->with(['property.address', 'consumptions.consumptionType', 'consumptions.items'])
                ->orderBy('issue_date')
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'casa' => $this->address($invoice->property?->address),
                    'numero' => $invoice->invoice_number,
                    'fornecedor' => $invoice->supplier,
                    'emitida_em' => $this->day($invoice->issue_date),
                    'periodo' => [$this->day($invoice->period_start), $this->day($invoice->period_end)],
                    'total' => $invoice->total_amount,
                    'tem_pdf' => $invoice->file_id !== null,
                    'consumos' => $invoice->consumptions->map(fn (Consumption $consumption) => [
                        'tipo' => $consumption->consumptionType?->name,
                        'quantidade' => $consumption->amount,
                        'unidade' => $consumption->consumptionType?->unit_of_measure,
                        'custo' => $consumption->cost,
                        'linhas' => $consumption->items->map(fn ($item) => [
                            'nome' => $item->name,
                            'categoria' => $item->category,
                            'quantidade' => $item->amount,
                            'unidade' => $item->unit,
                            'custo' => $item->cost,
                        ])->all(),
                    ])->all(),
                ])->all(),
            'documentos_cofre' => Document::whereIn('property_id', $owned)
                ->with(['category', 'property.address'])
                ->orderBy('issue_date')
                ->get()
                ->map(fn (Document $document) => [
                    'casa' => $this->address($document->property?->address),
                    'categoria' => $document->category?->name,
                    'nome' => $document->name,
                    'descricao' => $document->description,
                    'emitido_em' => $this->day($document->issue_date),
                    'valido_ate' => $this->day($document->expiration_date),
                    'tem_ficheiro' => $document->file_id !== null,
                ])->all(),
            'pedidos_assistencia' => AssistanceRequest::where('resident_id', $resident->id)
                ->with(['problemCategory', 'property.address', 'proposals.serviceProvider'])
                ->oldest()
                ->get()
                ->map(fn (AssistanceRequest $request) => [
                    'casa' => $this->address($request->property?->address),
                    'categoria' => $request->problemCategory?->name,
                    'titulo' => $request->title,
                    'descricao' => $request->description,
                    'estado' => $request->status,
                    'aberto_em' => $this->moment($request->created_at),
                    'fechado_em' => $this->moment($request->closed_at),
                    'propostas_recebidas' => $request->proposals->map(fn (Proposal $proposal) => [
                        'empresa' => $proposal->serviceProvider?->company_name,
                        'valor' => $proposal->estimated_value,
                        'estado' => $proposal->status,
                        'data_disponivel' => $this->day($proposal->available_date),
                    ])->all(),
                ])->all(),
        ];
    }

    private function provider(User $user): array
    {
        $provider = $user->service_provider->load(['zones', 'specialties.category', 'specialties.specialty']);

        return [
            'empresa' => $provider->company_name,
            'nif' => $provider->nif,
            'telefone' => $provider->phone,
            'email' => $provider->provider_email,
            'descricao' => $provider->description,
            'zonas' => $provider->zones->map(fn ($zone) => "{$zone->location}, {$zone->county}")->all(),
            'especialidades' => $provider->specialties->map(fn ($specialty) => $specialty->display_name)->all(),
            // the requests answered appear by title only: the resident behind them is not the user's data
            'propostas_enviadas' => $provider->proposals()->with('assistanceRequest')->oldest()->get()
                ->map(fn (Proposal $proposal) => [
                    'pedido' => $proposal->assistanceRequest?->title,
                    'valor' => $proposal->estimated_value,
                    'descricao' => $proposal->description,
                    'estado' => $proposal->status,
                    'data_disponivel' => $this->day($proposal->available_date),
                    'enviada_em' => $this->moment($proposal->created_at),
                ])->all(),
        ];
    }

    private function partner(User $user): array
    {
        $partner = $user->partner->load('offers.category');

        return [
            'empresa' => $partner->company_name,
            'nif' => $partner->nif,
            'telefone' => $partner->phone,
            'website' => $partner->website,
            'descricao' => $partner->description,
            'ofertas' => $partner->offers->map(fn ($offer) => [
                'titulo' => $offer->title,
                'descricao' => $offer->description,
                'tipo' => $offer->category?->name,
                'url' => $offer->url,
                'inicio' => $this->day($offer->start_date),
                'fim' => $this->day($offer->end_date),
                'ativa' => (bool) $offer->active,
            ])->all(),
        ];
    }

    private function condominiumAdmin(User $user): array
    {
        $admin = $user->condominium_admin->load('condominia.address');

        return [
            'contacto' => $admin->contact,
            'tipo' => $admin->admin_type,
            'condominios' => $admin->condominia->map(fn ($condominium) => [
                'nome' => $condominium->name,
                'morada' => $this->address($condominium->address),
                'fundo_reserva' => $condominium->reserve_fund,
            ])->all(),
        ];
    }

    private function address(?Address $address): ?string
    {
        if (!$address) {
            return null;
        }

        $street = trim(implode(', ', array_filter([$address->street, $address->door, $address->fraction])));

        return implode(', ', array_filter([$street, $address->postal_code, $address->location, $address->county]));
    }

    private function day(mixed $date): ?string
    {
        return $date instanceof CarbonInterface ? $date->toDateString() : $date;
    }

    private function moment(mixed $date): ?string
    {
        return $date instanceof CarbonInterface ? $date->toIso8601String() : $date;
    }
}