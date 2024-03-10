<?php

namespace App\Enums;

enum SortTypesEnum: string {
    case NAME = 'name';
    case INACTIVE = 'date';
    case SIZE = 'size';
    case WIDTH = 'width';
    case HEIGHT = 'height';
    case RATIO = 'ratio';
}
