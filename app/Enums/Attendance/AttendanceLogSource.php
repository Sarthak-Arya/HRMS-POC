<?php

namespace App\Enums\Attendance;

enum AttendanceLogSource: string
{
    case WEB = 'web';
    case MOBILE = 'mobile';
    case BIOMETRIC = 'biometric';
    case API = 'api';
}
