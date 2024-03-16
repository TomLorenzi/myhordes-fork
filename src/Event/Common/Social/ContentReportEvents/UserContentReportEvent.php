<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Entity\AdminReport;
use App\Entity\User;

/**
 * @property-read User $subject
 * @method setup( User $reporter, AdminReport $report, User $subject, int $count = 1 )
 */
class UserContentReportEvent extends ContentReportEvent {}