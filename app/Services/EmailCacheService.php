<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class EmailCacheService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'emails:';
    private const USER_INFO_CACHE_PREFIX = 'user_info:';
    
    /**
     * Generate a cache key for emails based on user session
     */
    private function getEmailCacheKey(): string
    {
        $userId = $this->getUserId();
        return self::CACHE_PREFIX . $userId;
    }
    
    /**
     * Generate a cache key for user info based on user session
     */
    private function getUserInfoCacheKey(): string
    {
        $userId = $this->getUserId();
        return self::USER_INFO_CACHE_PREFIX . $userId;
    }
    
    /**
     * Get a unique user identifier from the session
     */
    private function getUserId(): string
    {
        // Use the session ID as a unique identifier
        // In a production app, you might use the actual user ID from the Graph API
        return Session::getId();
    }
    
    /**
     * Check if emails are cached and not expired
     */
    public function hasValidEmailCache(): bool
    {
        try {
            return Cache::has($this->getEmailCacheKey());
        } catch (\Exception $e) {
            // If cache fails, assume no cache exists
            return false;
        }
    }
    
    /**
     * Get cached emails
     */
    public function getCachedEmails(): ?array
    {
        $cached = Cache::get($this->getEmailCacheKey());
        
        if ($cached && isset($cached['emails'], $cached['cached_at'])) {
            return [
                'emails' => $cached['emails'],
                'cached_at' => $cached['cached_at'],
                'expires_at' => $cached['expires_at']
            ];
        }
        
        return null;
    }
    
    /**
     * Cache emails with metadata
     */
    public function cacheEmails(array $emails): void
    {
        try {
            $now = Carbon::now();
            $expiresAt = $now->copy()->addSeconds(self::CACHE_TTL);
            
            $cacheData = [
                'emails' => $emails,
                'cached_at' => $now->toISOString(),
                'expires_at' => $expiresAt->toISOString(),
                'count' => count($emails)
            ];
            
            Cache::put($this->getEmailCacheKey(), $cacheData, self::CACHE_TTL);
        } catch (\Exception $e) {
            // If caching fails, silently continue without caching
            // This allows the app to work even if cache is not properly configured
        }
    }
    
    /**
     * Check if user info is cached and not expired
     */
    public function hasValidUserInfoCache(): bool
    {
        return Cache::has($this->getUserInfoCacheKey());
    }
    
    /**
     * Get cached user info
     */
    public function getCachedUserInfo(): ?array
    {
        $cached = Cache::get($this->getUserInfoCacheKey());
        
        if ($cached && isset($cached['user_info'], $cached['cached_at'])) {
            return [
                'user_info' => $cached['user_info'],
                'cached_at' => $cached['cached_at'],
                'expires_at' => $cached['expires_at']
            ];
        }
        
        return null;
    }
    
    /**
     * Cache user info with metadata
     */
    public function cacheUserInfo(array $userInfo): void
    {
        $now = Carbon::now();
        $expiresAt = $now->copy()->addSeconds(self::CACHE_TTL);
        
        $cacheData = [
            'user_info' => $userInfo,
            'cached_at' => $now->toISOString(),
            'expires_at' => $expiresAt->toISOString()
        ];
        
        Cache::put($this->getUserInfoCacheKey(), $cacheData, self::CACHE_TTL);
    }
    
    /**
     * Clear all cached data for the current user
     */
    public function clearCache(): void
    {
        Cache::forget($this->getEmailCacheKey());
        Cache::forget($this->getUserInfoCacheKey());
    }
    
    /**
     * Clear only email cache for the current user
     */
    public function clearEmailCache(): void
    {
        Cache::forget($this->getEmailCacheKey());
    }
    
    /**
     * Clear only user info cache for the current user
     */
    public function clearUserInfoCache(): void
    {
        Cache::forget($this->getUserInfoCacheKey());
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $emailCache = $this->getCachedEmails();
        $userInfoCache = $this->getCachedUserInfo();
        
        return [
            'emails' => [
                'cached' => $emailCache !== null,
                'cached_at' => $emailCache['cached_at'] ?? null,
                'expires_at' => $emailCache['expires_at'] ?? null,
                'count' => $emailCache ? count($emailCache['emails']) : 0
            ],
            'user_info' => [
                'cached' => $userInfoCache !== null,
                'cached_at' => $userInfoCache['cached_at'] ?? null,
                'expires_at' => $userInfoCache['expires_at'] ?? null
            ],
            'cache_ttl_seconds' => self::CACHE_TTL
        ];
    }
    
    /**
     * Set custom TTL for cache (useful for testing or different scenarios)
     */
    public function setCacheTTL(int $seconds): void
    {
        // Note: This would require refactoring to use dynamic TTL
        // For now, it's here as a placeholder for future enhancement
    }
    
    /**
     * Check if cache is about to expire (within 10% of TTL)
     */
    public function isCacheAboutToExpire(): bool
    {
        $emailCache = $this->getCachedEmails();
        
        if (!$emailCache) {
            return false;
        }
        
        $expiresAt = Carbon::parse($emailCache['expires_at']);
        $now = Carbon::now();
        $secondsUntilExpiry = $expiresAt->diffInSeconds($now, false);
        
        // Return true if cache expires within 10% of total TTL (30 seconds for 5 min TTL)
        return $secondsUntilExpiry <= (self::CACHE_TTL * 0.1);
    }
}
