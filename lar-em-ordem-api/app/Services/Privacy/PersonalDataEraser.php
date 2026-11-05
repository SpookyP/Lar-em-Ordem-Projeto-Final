<?php

namespace App\Services\Privacy;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\Partner;
use App\Models\User\Resident;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\Property\Address;
use App\Models\Property\Property;
use App\Models\Vault\Document;
use App\Models\Vault\Notification;
use App\Services\Invoice\InvoiceService;
use App\Services\ServiceProvider\AssistanceRequestService;
use App\Services\Vault\DocumentService;
use Illuminate\Support\Facades\DB;
/**
 * Removes the personal data of a profile (RGPD art. 17) so a soft-deleted row no longer
 * identifies anyone. Each profile service calls the matching method before soft-deleting the
 * profile, inside its own transaction. Files are removed through each module's own service.
 */
class PersonalDataEraser
{
    public const REMOVED_NAME = 'Utilizador removido';
    public const REMOVED_COMPANY = 'Empresa removida';
    public const REMOVED_NIF = '000000000';
    public const REMOVED_PHONE = '000000000';
    public const REMOVED_REQUEST = 'Pedido removido';

    public function __construct(
        private InvoiceService $invoices,
        private DocumentService $documents,
        private AssistanceRequestService $requests,
    ) {}

    public function eraseResident(Resident $resident): void
    {
        $this->invoices->deleteAllForResident($resident);
        $this->requests->eraseForResident($resident);

        // residents don't share properties, so the properties go with the resident (also those already deleted in the app)
        Property::withTrashed()
            ->whereIn('id', $resident->contracts()->withTrashed()->pluck('property_id'))
            ->get()
            ->each(fn (Property $property) => $this->eraseProperty($property));

        $resident->contracts()->where('is_active', true)->update(['is_active' => false, 'end_date' => now()]);
        $resident->contracts()->delete();
        DB::table('condominium_notice_reads')->where('resident_id', $resident->id)->delete();

        $resident->forceFill(['nif' => self::REMOVED_NIF, 'is_active' => false])->save();
    }

    public function eraseServiceProvider(ServiceProviderModel $provider): void
    {
        $provider->zones()->detach();
        $provider->specialties()->delete();

        $provider->forceFill([
            'company_name' => self::REMOVED_COMPANY,
            'nif' => self::REMOVED_NIF,
            'phone' => self::REMOVED_PHONE,
            'provider_email' => null,
            'description' => '',
        ])->save();
    }

    public function erasePartner(Partner $partner): void
    {
        $partner->forceFill([
            'company_name' => self::REMOVED_COMPANY,
            'nif' => self::REMOVED_NIF,
            'phone' => self::REMOVED_PHONE,
            'website' => null,
            'description' => '',
        ])->save();
    }

    public function eraseCondominiumAdmin(CondominiumAdmin $admin): void
    {
        $admin->forceFill(['contact' => 'removido'])->save();
    }

    private function eraseProperty(Property $property): void
    {
        Document::where('property_id', $property->id)->get()
            ->each(fn (Document $document) => $this->documents->deleteDocument($document));

        // deleteDocument only removes the alerts whose text names the document; here all of them go
        Notification::where('property_id', $property->id)->delete();

        $address = Address::withTrashed()->find($property->address_id);

        if ($address) {
            // district, county and locality identify no one; what pinpoints the property goes
            $address->forceFill([
                'street' => 'Morada removida',
                'postal_code' => '0000-000',
                'door' => null,
                'fraction' => null,
            ])->save();
            $address->delete();
        }

        $property->forceFill(['condominium_id' => null])->save();
        $property->delete();
    }
}