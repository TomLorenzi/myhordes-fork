<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Entity\AdminReport;
use App\Entity\CitizenRankingProxy;
use App\Entity\User;

/**
 * @property-read CitizenRankingProxy $subject
 * @method setup( User $reporter, AdminReport $report, CitizenRankingProxy $subject, int $count = 1 )
 */
class CitizenRankingProxyContentReportEvent extends ContentReportEvent {}