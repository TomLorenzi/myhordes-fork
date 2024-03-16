<?php

namespace App\Event\Common\Social\ContentReportEvents;


use App\Entity\AdminReport;
use App\Entity\User;

readonly class ContentReportData
{
    public User $reporter;
    public AdminReport $report;
    public mixed $subject;
    public int $count;

    /**
     * @param User $reporter
     * @param AdminReport $report
     * @param User $subject
     * @param int $count
     * @return ContentReportEvent
     * @noinspection PhpDocSignatureInspection
     */
    public function setup( User $reporter, AdminReport $report, mixed $subject, int $count = 1 ): void {
        $this->reporter = $reporter;
        $this->report = $report;
        $this->subject = $subject;
        $this->count = max(1, $count);
    }
}