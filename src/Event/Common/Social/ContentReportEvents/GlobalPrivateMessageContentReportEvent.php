<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Entity\AdminReport;
use App\Entity\GlobalPrivateMessage;
use App\Entity\User;

/**
 * @property-read GlobalPrivateMessage $subject
 * @method setup( User $reporter, AdminReport $report, GlobalPrivateMessage $subject, int $count = 1 )
 */
class GlobalPrivateMessageContentReportEvent extends ContentReportEvent {}