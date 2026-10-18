<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Core\Models\Concerns\Archivable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // Un compte archivé ne peut plus se connecter ; ses ventes et inventaires gardent son nom
    use HasFactory, Notifiable, Archivable;

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
        return $this->hasMany(\App\Modules\Sale\Models\Sale::class);
    }

    /**
     * Check if user is back office
     */
    public function isBackOffice(): bool
    {
        return $this->role === 'back_office';
    }

    /**
     * Check if user is front office
     */
    public function isFrontOffice(): bool
    {
        return $this->role === 'front_office';
    }

    public function archiveBlocker(): ?string
    {
        return $this->is(auth()->user()) ? 'vous ne pouvez pas archiver votre propre compte' : null;
    }
}
