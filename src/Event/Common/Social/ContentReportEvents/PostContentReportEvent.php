<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Entity\AdminReport;
use App\Entity\Post;
use App\Entity\User;

/**
 * @property-read Post $subject
 * @method setup( User $reporter, AdminReport $report, Post $subject, int $count = 1 )
 */
class PostContentReportEvent extends ContentReportEvent {}