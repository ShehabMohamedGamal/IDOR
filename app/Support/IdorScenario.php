<?php

namespace App\Support;

final class IdorScenario
{
    public const SAFE = 'safe';
    public const BASIC_ALL = 'basic_all';
    public const PROFILE_UPDATE_ONLY = 'profile_update_only';
    public const HIDDEN_PARAMS_REVIEW_STORE = 'hidden_params_review_store';
    public const INDIRECT_REFS_REVIEW_UPDATE = 'indirect_refs_review_update';
    public const UUID_REVIEW_UPDATE = 'uuid_review_update';

    public static function current(): string
    {
        $scenario = (string) config('security.idor_scenario', self::SAFE);

        return in_array($scenario, self::allowed(), true) ? $scenario : self::SAFE;
    }

    /**
     * @return list<string>
     */
    public static function allowed(): array
    {
        return [
            self::SAFE,
            self::BASIC_ALL,
            self::PROFILE_UPDATE_ONLY,
            self::HIDDEN_PARAMS_REVIEW_STORE,
            self::INDIRECT_REFS_REVIEW_UPDATE,
            self::UUID_REVIEW_UPDATE,
        ];
    }

    public static function bypassReviewOwnership(): bool
    {
        return self::current() === self::BASIC_ALL;
    }

    public static function bypassBookAuthorization(): bool
    {
        return self::current() === self::BASIC_ALL;
    }

    public static function bypassProfileViewAuthorization(): bool
    {
        return self::current() === self::BASIC_ALL;
    }

    public static function bypassProfileUpdateAuthorization(): bool
    {
        return in_array(self::current(), [self::BASIC_ALL, self::PROFILE_UPDATE_ONLY], true);
    }

    public static function allowHiddenReviewUserIdParameter(): bool
    {
        return self::current() === self::HIDDEN_PARAMS_REVIEW_STORE;
    }

    public static function bypassIndirectReviewOwnership(): bool
    {
        return in_array(self::current(), [self::BASIC_ALL, self::INDIRECT_REFS_REVIEW_UPDATE], true);
    }

    public static function bypassUuidReviewOwnership(): bool
    {
        return in_array(self::current(), [self::BASIC_ALL, self::UUID_REVIEW_UPDATE], true);
    }
}
