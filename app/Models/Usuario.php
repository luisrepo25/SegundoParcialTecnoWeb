<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $appends = ['role', 'name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre', 'apellido', 'email', 'password_hash',
        'dni', 'telefono', 'direccion', 'id_rol',
        'activo', 'bloqueado', 'foto_perfil',
    ];

    public function profesor(): HasOne
    {
        return $this->hasOne(Profesor::class, 'id_usuario', 'id_usuario');
    }

    public function estudiante(): HasOne
    {
        return $this->hasOne(Estudiante::class, 'id_usuario', 'id_usuario');
    }

    public function personalAdministrativo(): HasOne
    {
        return $this->hasOne(PersonalAdministrativo::class, 'id_usuario', 'id_usuario');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'password_hash',
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
            'activo' => 'boolean',
            'bloqueado' => 'boolean',
        ];
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // Accessors and Mutators for compatibility

    public function getNameAttribute(): string
    {
        return trim(($this->nombre ?? '') . ' ' . ($this->apellido ?? ''));
    }

    public function setNameAttribute($value)
    {
        $parts = explode(' ', trim($value), 2);
        $this->attributes['nombre'] = $parts[0];
        $this->attributes['apellido'] = $parts[1] ?? '';
    }

    public function getPasswordAttribute()
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = $value;
    }

    public function getRoleAttribute(): string
    {
        return match ($this->id_rol ?? null) {
            1 => 'propietario',
            2 => 'director',
            3 => 'secretaria',
            4 => 'profesor',
            5 => 'estudiante',
            default => 'estudiante',
        };
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['id_rol'] = match ($value) {
            'propietario' => 1,
            'director'    => 2,
            'secretaria'  => 3,
            'profesor'    => 4,
            'estudiante'  => 5,
            default       => 5,
        };
    }

    public function getIdAttribute()
    {
        return $this->id_usuario;
    }

    // Authentication Overrides

    public function getAuthPassword(): string
    {
        $hash = $this->password_hash ?? '';
        // Java BCrypt uses $2a$ prefix; PHP password_get_info() only recognizes $2y$
        if (str_starts_with($hash, '$2a$')) {
            $hash = '$2y$' . substr($hash, 4);
        }
        return $hash;
    }

    public function hasVerifiedEmail(): bool
    {
        return true;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Do nothing
    }

    public function getRememberTokenName()
    {
        return '';
    }

    // JWT Subject Implementation

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }
}
