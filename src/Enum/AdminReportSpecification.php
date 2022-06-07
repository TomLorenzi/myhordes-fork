<?php

namespace App\Enum;

enum AdminReportSpecification: int {

    case None = 0;
    case CitizenAnnouncement = 1;
    case CitizenLastWords = 2;
    case CitizenTownComment = 3;

}