<?php

namespace App\Support;

use App\Models\Review;

final class IndirectReviewReference
{
    public static function encoded(int $reviewId): string
    {
        return 'enc_'.self::base64UrlEncode((string) $reviewId);
    }

    public static function filename(int $reviewId): string
    {
        return "review-{$reviewId}.txt";
    }

    public static function hash(int $reviewId): string
    {
        return 'hash_'.substr(hash('sha256', "review:{$reviewId}"), 0, 16);
    }

    /**
     * UUID-looking identifier used for UUID scenario training.
     */
    public static function uuid(int $reviewId): string
    {
        $hex = hash('sha256', "review:uuid:{$reviewId}");

        return sprintf(
            '%s-%s-4%s-a%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 13, 3),
            substr($hex, 17, 3),
            substr($hex, 20, 12),
        );
    }

    /**
     * @return array{encoded: string, filename: string, hash: string, uuid: string}
     */
    public static function samples(int $reviewId): array
    {
        return [
            'encoded' => self::encoded($reviewId),
            'filename' => self::filename($reviewId),
            'hash' => self::hash($reviewId),
            'uuid' => self::uuid($reviewId),
        ];
    }

    public static function resolve(string $reference): ?Review
    {
        if (preg_match('/^review-(\d+)\.txt$/', $reference, $matches) === 1) {
            return Review::query()->find((int) $matches[1]);
        }

        if (str_starts_with($reference, 'enc_')) {
            $decodedId = self::base64UrlDecodeToInt(substr($reference, 4));

            return $decodedId === null ? null : Review::query()->find($decodedId);
        }

        if (str_starts_with($reference, 'hash_')) {
            return Review::query()
                ->get()
                ->first(fn (Review $review) => self::hash($review->id) === $reference);
        }

        return null;
    }

    public static function resolveUuid(string $uuid): ?Review
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-a[0-9a-f]{3}-[0-9a-f]{12}$/', strtolower($uuid)) !== 1) {
            return null;
        }

        return Review::query()
            ->get()
            ->first(fn (Review $review) => self::uuid($review->id) === strtolower($uuid));
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecodeToInt(string $value): ?int
    {
        $padding = strlen($value) % 4;

        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false || ! ctype_digit($decoded)) {
            return null;
        }

        $id = (int) $decoded;

        return $id > 0 ? $id : null;
    }
}
