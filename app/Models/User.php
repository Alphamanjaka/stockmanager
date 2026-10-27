<?php

namespace App\Models;

use App\Core\Models\Concerns\Archivable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Support\Role;
use App\Modules\Sale\Models\Sale;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // Un compte archivé ne peut plus se connecter ; ses ventes et inventaires gardent son nom
    use Archivable, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

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

    /**
     * Get the sales for the user.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /** Travaille dans le back office : administrateur, gérant ou magasinier (pages selon le rôle : Role). */
    public function isBackOffice(): bool
    {
        return Role::isBackOffice($this->role);
    }

    /** Administrateur : tout le back office, dont les utilisateurs et les paramètres. */
    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    /** Vendeur : la caisse (front office). */
    public function isFrontOffice(): bool
    {
        return $this->role === Role::SELLER;
    }

    /** Le compte peut-il ouvrir la route $name du back office ? */
    public function canOpen(string $name): bool
    {
        return Role::allows($this->role, $name);
    }

    public function archiveBlocker(): ?string
    {
        return $this->is(auth()->user()) ? 'vous ne pouvez pas archiver votre propre compte' : null;
    }
}
