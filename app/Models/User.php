<?php

namespace App\Models;

use App\Notifications\SchoolPassResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'school_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'must_change_password',
        'password_changed_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Administración estructural de la escuela.
     */
    public function canManageSchool(): bool
    {
        return $this->hasRole(
            'superadmin',
            'school_admin'
        );
    }

    /**
     * Consulta administrativa y reportes.
     */
    public function canViewSchoolAdministration(): bool
    {
        return $this->hasRole(
            'superadmin',
            'school_admin',
            'director'
        );
    }

    /**
     * Operación de escaneo y control de acceso.
     */
    public function canOperateAccess(): bool
    {
        return $this->hasRole(
            'superadmin',
            'school_admin',
            'director',
            'prefect',
            'kiosk'
        );
    }

    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    public function sendPasswordResetNotification(
        $token
    ): void {
        $this->notify(
            new SchoolPassResetPasswordNotification(
                $token
            )
        );
    }
}
