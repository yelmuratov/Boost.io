<?php
namespace App\Enums;

enum UserRoles: string
{
    case Admin = 'admin';
    case User = 'user';

    public static function values():array
    {
        return array_column(self::cases(), 'value');
    }

    public function hasAdminPermission(): bool
    {
        return $this->value === self::Admin->value;
    }
}
