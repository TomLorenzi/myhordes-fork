<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Entity\AdminReport;
use App\Entity\PrivateMessage;
use App\Entity\User;

/**
 * @property-read PrivateMessage $subject
 * @method setup( User $reporter, AdminReport $report, PrivateMessage $subject, int $count = 1 )
 */
class PrivateMessageContentReportEvent extends ContentReportEvent {}