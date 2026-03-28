<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    public const ROLE_DOSI = 'DOSI';
    public const ROLE_DIRECTEUR_CMC = 'DIRECTEUR_CMC';
    public const ROLE_DIRECTEUR_EFP = 'DIRECTEUR_EFP';
    public const ROLE_CHEF_POLE = 'CHEF_POLE';
    public const ROLE_FORMATEUR = 'FORMATEUR';

    public const ROLES = [
        self::ROLE_DOSI,
        self::ROLE_DIRECTEUR_CMC,
        self::ROLE_DIRECTEUR_EFP,
        self::ROLE_CHEF_POLE,
        self::ROLE_FORMATEUR,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $table = 'users';
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'matricule',
        'password',
        'role',
        'id_etablissement',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public $timestamps = false;

    public function formateur()
    {
        return $this->hasOne(Formateur::class, 'id_user');
    }

    public function polesAsChef()
    {
        return $this->hasMany(Pole::class, 'id_chef');
    }

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class, 'id_etablissement');
    }
}
