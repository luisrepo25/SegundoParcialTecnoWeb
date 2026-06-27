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
            1 => 'admin',
            2 => 'director',
            3 => 'secretary',
            4 => 'teacher',
            5 => 'student',
            default => 'student',
        };
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['id_rol'] = match ($value) {
            'admin' => 1,
            'director' => 2,
            'secretary' => 3,
            'teacher' => 4,
            'student' => 5,
            default => 5,
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
