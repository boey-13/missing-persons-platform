<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Volunteer = 'volunteer';
    case Admin = 'admin';
}


