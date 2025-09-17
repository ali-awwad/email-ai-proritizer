<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class EmailCacheService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const AI_CACHE_TTL = 3600; // 1 hour for AI analysis
    private const CACHE_PREFIX = 'emails:';
    private const USER_INFO_CACHE_PREFIX = 'user_info:';
    private const AI_ANALYSIS_CACHE_PREFIX = 'ai_analysis:';
    
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
     * Generate a cache key for AI analysis based on email content hash
     */
    private function getAIAnalysisCacheKey(array $email): string
    {
        $userId = $this->getUserId();
        $contentHash = md5(($email['subject'] ?? '') . ($email['body_preview'] ?? '') . ($email['from'] ?? ''));
        return self::AI_ANALYSIS_CACHE_PREFIX . $userId . ':' . $contentHash;
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
    
    /**
     * Check if AI analysis is cached for a specific email
     */
    public function hasAIAnalysis(array $email): bool
    {
        try {
            return Cache::has($this->getAIAnalysisCacheKey($email));
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get cached AI analysis for an email
     */
    public function getAIAnalysis(array $email): ?array
    {
        try {
            $cached = Cache::get($this->getAIAnalysisCacheKey($email));
            
            if ($cached && isset($cached['analysis'], $cached['cached_at'])) {
                return [
                    'analysis' => $cached['analysis'],
                    'cached_at' => $cached['cached_at'],
                    'expires_at' => $cached['expires_at']
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Cache AI analysis for an email
     */
    public function cacheAIAnalysis(array $email, array $analysis): void
    {
        try {
            $now = Carbon::now();
            $expiresAt = $now->copy()->addSeconds(self::AI_CACHE_TTL);
            
            $cacheData = [
                'analysis' => $analysis,
                'cached_at' => $now->toISOString(),
                'expires_at' => $expiresAt->toISOString(),
                'email_hash' => md5(($email['subject'] ?? '') . ($email['body_preview'] ?? ''))
            ];
            
            Cache::put($this->getAIAnalysisCacheKey($email), $cacheData, self::AI_CACHE_TTL);
        } catch (\Exception $e) {
            // Silently fail if caching is not available
        }
    }
    
    /**
     * Clear all cached data for the current user including AI analysis
     */
    public function clearAllCache(): void
    {
        $this->clearCache();
        $this->clearUserAIAnalysisCache();
    }
    
    /**
     * Clear all AI analysis cache for the current user
     */
    public function clearUserAIAnalysisCache(): void
    {
        try {
            $userId = $this->getUserId();
            $pattern = self::AI_ANALYSIS_CACHE_PREFIX . $userId . ':*';
            
            // Note: This is a simplified approach. In production with Redis,
            // you might want to use a more efficient pattern matching approach
            // For now, we rely on TTL expiration
        } catch (\Exception $e) {
            // Silently fail
        }
    }
    
    /**
     * Get AI analysis cache statistics
     */
    public function getAIAnalysisStats(array $emails): array
    {
        $stats = [
            'total_emails' => count($emails),
            'cached_analyses' => 0,
            'cache_hit_rate' => 0,
            'cache_ttl_seconds' => self::AI_CACHE_TTL
        ];
        
        foreach ($emails as $email) {
            if ($this->hasAIAnalysis($email)) {
                $stats['cached_analyses']++;
            }
        }
        
        if ($stats['total_emails'] > 0) {
            $stats['cache_hit_rate'] = round(($stats['cached_analyses'] / $stats['total_emails']) * 100, 1);
        }
        
        return $stats;
    }
}
