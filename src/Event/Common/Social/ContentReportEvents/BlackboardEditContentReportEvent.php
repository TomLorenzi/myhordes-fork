<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Entity\AdminReport;
use App\Entity\BlackboardEdit;
use App\Entity\User;

/**
 * @property-read BlackboardEdit $subject
 * @method setup( User $reporter, AdminReport $report, BlackboardEdit $subject, int $count = 1 )
 */
class BlackboardEditContentReportEvent extends ContentReportEvent {}