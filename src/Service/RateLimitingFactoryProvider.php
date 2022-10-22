<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class RateLimitingFactoryProvider
{
    private CamelCaseToSnakeCaseNameConverter $normalizer;

    public RateLimiterFactory $publicApi;
    public RateLimiterFactory $anonymousApi;
    public RateLimiterFactory $authenticatedPersonalApi;
    public RateLimiterFactory $authenticatedApi;
    public RateLimiterFactory $blackboardEditSlide;
    public RateLimiterFactory $blackboardEditFixed;
    public RateLimiterFactory $forumThreadCreation;
    public RateLimiterFactory $reportToModeration;
    public RateLimiterFactory $reportToModerationLimited;

    public function __construct(
        RateLimiterFactory $publicApiLimiter,
        RateLimiterFactory $anonymousApiLimiter,
        RateLimiterFactory $authenticatedPersonalApiLimiter,
        RateLimiterFactory $authenticatedApiLimiter,
        RateLimiterFactory $blackboardEditSlideLimiter,
        RateLimiterFactory $blackboardEditFixedLimiter,
        RateLimiterFactory $forumThreadCreationLimiter,
        RateLimiterFactory $reportToModerationLimiter,
        RateLimiterFactory $reportToModerationLimitedLimiter,
    ) {
        $this->publicApi = $publicApiLimiter;
        $this->anonymousApi = $anonymousApiLimiter;
        $this->authenticatedPersonalApi = $authenticatedPersonalApiLimiter;
        $this->authenticatedApi = $authenticatedApiLimiter;
        $this->blackboardEditSlide = $blackboardEditSlideLimiter;
        $this->blackboardEditFixed = $blackboardEditFixedLimiter;
        $this->forumThreadCreation = $forumThreadCreationLimiter;
        $this->reportToModeration = $reportToModerationLimiter;
        $this->reportToModerationLimited = $reportToModerationLimitedLimiter;

        $this->normalizer = new CamelCaseToSnakeCaseNameConverter();
    }

    public function byKey( string $key ): ?RateLimiterFactory {
        try {
            $camelCase = $this->normalizer->denormalize( $key );
        } catch (\Throwable $t) {
            return null;
        }

        return $this->$camelCase ?? null;
    }

    public function reportLimiter( bool|User $limited = false ): RateLimiterFactory {
        if (is_a( $limited, User::class )) $limited = $limited->hasRoleFlag( User::USER_ROLE_LIMIT_MODERATION );
        return $limited ? $this->reportToModerationLimited : $this->reportToModeration;
    }
}