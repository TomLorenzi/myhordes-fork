<?php

namespace App\Event\Common\Social\ContentReportEvents;

use App\Event\Event;

/**
 * @property-read ContentReportData $data
 * @mixin ContentReportData
 */
abstract class ContentReportEvent extends Event {
    protected static function configuration(): string { return ContentReportData::class; }
}