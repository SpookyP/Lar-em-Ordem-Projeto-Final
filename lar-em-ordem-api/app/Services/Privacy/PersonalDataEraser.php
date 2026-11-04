<?php

namespace App\Services\Privacy;

use App\Models\Condominium\CondominiumAdmin;
use App\Models\User\Partner;
use App\Models\User\Resident;
use App\Models\User\ServiceProvider as ServiceProviderModel;

/**
 * Overwrites the personal data of a profile (RGPD art. 17) so a soft-deleted row no longer
 * identifies anyone. Each profile service calls the matching method before soft-deleting the
 * profile, inside its own transaction.
 */
class PersonalDataEraser
{
    public const REMOVED_NAME = 'Utilizador removido';
    public const REMOVED_COMPANY = 'Empresa removida';
    public const REMOVED_NIF = '000000000';
    public const REMOVED_PHONE = '000000000';

    public function eraseResident(Resident $resident): void
    {
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
}