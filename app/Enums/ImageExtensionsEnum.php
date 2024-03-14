<?php

namespace App\Enums;

enum ImageExtensionsEnum: string {
    case JPG  = 'jpg';
    case JPEG = 'jpeg';
    case PNG  = 'png';
    case APNG = 'apng';
    case GIF  = 'gif';
    case WEBM = 'webm';
    case AVIF = 'avif';
    case BMP  = 'bmp';
}
