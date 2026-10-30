<?php

namespace App\Models\User;

use App\Notifications\ConfirmRegistration;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, HasRoles, HasUlids;

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function resident()
    {
        return $this->hasOne(Resident::class);
    }
    public function partner()
    {
        return $this->hasOne(Partner::class);
    }
    public function service_provider()
    {
        return $this->hasOne(ServiceProvider::class);
    }
    public function social_account()
    {
        return $this->hasOne(SocialAccount::class);
    }
    public function condominium_admin()
    {
        return $this->hasOne(\App\Models\Condominium\CondominiumAdmin::class);
    }

    /**
     * Sends the link to confirm a changed email. Overrides the MustVerifyEmail default so the email
     * is a ConfirmRegistration and the link opens the frontend instead of the API.
     */
    public function sendEmailVerificationNotification()
    {
        $minutes = 10;
        $id = $this->getKey();
        $hash = sha1($this->getEmailForVerification());

        // signed API URL, used only to take "expires" and "signature" from it
        $signed = URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $id,
            'hash' => $hash,
        ]);
        parse_str((string) parse_url($signed, PHP_URL_QUERY), $query);

        $url = config('oauth.frontend_url') . '/verify-email?' . http_build_query([
            'id' => $id,
            'hash' => $hash,
            'expires' => $query['expires'] ?? '',
            'signature' => $query['signature'] ?? '',
        ]);

        $this->notify(new ConfirmRegistration($this->name, $url, $minutes));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
