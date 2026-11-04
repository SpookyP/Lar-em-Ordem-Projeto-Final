<?php

namespace Tests\Feature\Privacy;

use App\Models\Condominium\Condominium;
use App\Models\Condominium\CondominiumAdmin;
use App\Models\Property\Address;
use App\Models\User\Partner;
use App\Models\User\Resident;
use App\Models\User\ServiceProvider as ServiceProviderModel;
use App\Models\User\SocialAccount;
use App\Models\User\User;
use App\Services\Privacy\PersonalDataEraser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Right to erasure (RGPD art. 17): deleting an account or a profile overwrites the personal data,
 * revokes every way back in and frees the email.
 */
class AccountErasureTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function deleteAccount(User $user): TestResponse
    {
        // logging out at the end needs the SPA session
        return $this->actingAs($user)
            ->withHeaders(['Referer' => 'http://' . config('sanctum.stateful')[0]])
            ->deleteJson('/api/user');
    }

    // ------------------------------------------------------------- the user row

    public function test_deleting_the_account_anonymises_the_user_and_revokes_every_way_in(): void
    {
        $user = $this->user('morador@example.com');
        $user->createToken('phone');
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'g-erase',
            'email' => $user->email,
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-device',
            'user_id' => $user->id,
            'payload' => '',
            'last_activity' => time(),
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset',
            'created_at' => now(),
        ]);

        $this->deleteAccount($user)->assertOk();

        $erased = User::withTrashed()->findOrFail($user->id);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertSame(PersonalDataEraser::REMOVED_NAME, $erased->name);
        $this->assertSame("removed-{$user->id}@anonymized.invalid", $erased->email);
        $this->assertNotNull($erased->anonymized_at);
        $this->assertNull($erased->email_verified_at);
        $this->assertFalse(Hash::check('password', $erased->password));
        $this->assertSame(0, $erased->roles()->count());
        $this->assertSame(0, $erased->tokens()->count());
        $this->assertSame(0, SocialAccount::withTrashed()->where('user_id', $user->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'other-device']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'morador@example.com']);
    }

    public function test_the_email_can_be_used_again_after_the_account_is_deleted(): void
    {
        $this->deleteAccount($this->user('morador@example.com'))->assertOk();

        Notification::fake();
        Http::fake(); // the "uncompromised" password rule would call an external service

        $password = 'Lr-Erase-2026#9a';

        $this->postJson('/api/auth/register', [
            'name' => 'Nova Conta',
            'email' => 'morador@example.com',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'resident',
            'nif' => '212345678',
        ])->assertStatus(202);
    }

    // --------------------------------------------------------------- profiles

    public function test_the_resident_profile_loses_its_nif(): void
    {
        $user = $this->user('morador@example.com');
        $residentId = $user->resident->id;

        $this->deleteAccount($user)->assertOk();

        $this->assertSoftDeleted('residents', ['id' => $residentId]);
        $this->assertSame(PersonalDataEraser::REMOVED_NIF, Resident::withTrashed()->findOrFail($residentId)->nif);
    }

    public function test_removing_only_the_provider_profile_scrubs_it_and_keeps_the_account(): void
    {
        $user = $this->user('residente@example.com'); // resident + provider
        $provider = $user->service_provider;

        $this->actingAs($user)
            ->deleteJson('/api/user/profile', ['role' => 'service_provider'])
            ->assertOk();

        $erased = ServiceProviderModel::withTrashed()->findOrFail($provider->id);

        $this->assertSoftDeleted('service_providers', ['id' => $provider->id]);
        $this->assertSame(PersonalDataEraser::REMOVED_COMPANY, $erased->company_name);
        $this->assertSame(PersonalDataEraser::REMOVED_NIF, $erased->nif);
        $this->assertSame(PersonalDataEraser::REMOVED_PHONE, $erased->phone);
        $this->assertNull($erased->provider_email);
        $this->assertSame('', $erased->description);
        $this->assertSame(0, DB::table('service_provider_service_zone')->where('service_provider_id', $provider->id)->count());
        $this->assertSame(0, DB::table('provider_specialties')->where('service_provider_id', $provider->id)->count());

        // only the profile went: the account and the other profile stay as they were
        $this->assertSame('residente@example.com', $user->fresh()->email);
        $this->assertNotNull($user->fresh()->resident);
    }

    public function test_deleting_a_partner_account_scrubs_the_company(): void
    {
        $user = $this->user('parceiro@example.com');
        $partnerId = $user->partner->id;

        $this->deleteAccount($user)->assertOk();

        $erased = Partner::withTrashed()->findOrFail($partnerId);

        $this->assertSame(PersonalDataEraser::REMOVED_COMPANY, $erased->company_name);
        $this->assertSame(PersonalDataEraser::REMOVED_NIF, $erased->nif);
        $this->assertSame(PersonalDataEraser::REMOVED_PHONE, $erased->phone);
        $this->assertNull($erased->website);
        $this->assertSame('', $erased->description);
    }

    public function test_a_refused_deletion_changes_nothing(): void
    {
        $user = User::factory()->create(['name' => 'Gestora', 'email' => 'gestora@example.com']);
        $user->assignRole('condominium_admin');
        $admin = CondominiumAdmin::factory()->create(['user_id' => $user->id, 'contact' => '912345678']);
        Condominium::forceCreate([
            'condominium_admin_id' => $admin->id,
            'address_id' => Address::factory()->create()->id,
            'name' => 'Edifício Aurora',
            'invite_code' => 'AURORA26',
        ]);

        // still manages a condominium: the module refuses and the whole erasure rolls back
        $this->deleteAccount($user)->assertStatus(422);

        $user->refresh();
        $this->assertFalse($user->trashed());
        $this->assertSame('Gestora', $user->name);
        $this->assertSame('gestora@example.com', $user->email);
        $this->assertNull($user->anonymized_at);
        $this->assertSame('912345678', $admin->fresh()->contact);
    }
}