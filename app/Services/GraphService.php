<?php

namespace App\Services;

use League\OAuth2\Client\Provider\GenericProvider;
use Exception;

class GraphService
{
    private $provider;
    private $emailCacheService;
    private $aiService;
    
    public function __construct(EmailCacheService $emailCacheService, AIService $aiService)
    {
        $this->emailCacheService = $emailCacheService;
        $this->aiService = $aiService;
        $this->setupAuthProvider();
    }
    
    private function setupAuthProvider()
    {
        $this->provider = new GenericProvider([
            'clientId' => config('graph.client_id'),
            'clientSecret' => config('graph.client_secret'),
            'redirectUri' => config('graph.redirect_uri'),
            'urlAuthorize' => 'https://login.microsoftonline.com/consumers/oauth2/v2.0/authorize',
            'urlAccessToken' => 'https://login.microsoftonline.com/consumers/oauth2/v2.0/token',
            'urlResourceOwnerDetails' => '',
            'scopes' => implode(' ', config('graph.scopes')),
        ]);
    }
    
    public function getAuthorizationUrl(): string
    {
        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => implode(' ', config('graph.scopes'))
        ]);
        
        // Store state in session for security
        session(['oauth2state' => $this->provider->getState()]);
        
        return $authUrl;
    }
    
    public function handleCallback(string $code, string $state): bool
    {
        // Verify state to prevent CSRF attacks
        if (empty($state) || $state !== session('oauth2state')) {
            session()->forget('oauth2state');
            throw new Exception('Invalid state parameter');
        }
        
        session()->forget('oauth2state');
        
        try {
            // Get access token
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);
            
            // Store access token in session
            session(['graph_access_token' => $accessToken->getToken()]);
            session(['graph_refresh_token' => $accessToken->getRefreshToken()]);
            session(['graph_expires' => $accessToken->getExpires()]);
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Failed to get access token: ' . $e->getMessage());
        }
    }
    
    public function isAuthenticated(): bool
    {
        $token = session('graph_access_token');
        $expires = session('graph_expires');
        
        return !empty($token) && time() < $expires;
    }
    
    private function getValidAccessToken(): ?string
    {
        $token = session('graph_access_token');
        $expires = session('graph_expires');
        $refreshToken = session('graph_refresh_token');
        
        // If token is still valid, return it
        if (!empty($token) && time() < $expires) {
            return $token;
        }
        
        // If we have a refresh token, try to refresh
        if (!empty($refreshToken)) {
            try {
                $newAccessToken = $this->provider->getAccessToken('refresh_token', [
                    'refresh_token' => $refreshToken
                ]);
                
                // Update session with new token
                session(['graph_access_token' => $newAccessToken->getToken()]);
                session(['graph_refresh_token' => $newAccessToken->getRefreshToken()]);
                session(['graph_expires' => $newAccessToken->getExpires()]);
                
                return $newAccessToken->getToken();
            } catch (Exception $e) {
                // Refresh failed, user needs to re-authenticate
                $this->clearAuthentication();
                return null;
            }
        }
        
        return null;
    }
    
    public function clearAuthentication(): void
    {
        // Clear both authentication and cache when user logs out
        session()->forget(['graph_access_token', 'graph_refresh_token', 'graph_expires']);
        $this->emailCacheService->clearCache();
    }
    
    public function getUnreadEmails(int $limit = 5, int $offset = 0, bool $forceRefresh = false, bool $includeAI = true): array
    {
        // Check cache first unless force refresh is requested
        if (!$forceRefresh && $this->emailCacheService->hasValidEmailCache()) {
            $cachedData = $this->emailCacheService->getCachedEmails();
            $emails = $cachedData['emails'];
            
            // Add AI analysis if requested and available
            if ($includeAI && config('ai.enabled', true)) {
                $emails = $this->addAIAnalysisToEmails($emails);
            }
            
            return $emails;
        }
        
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }
        
        try {
            // First, try to get emails specifically from the Inbox folder
            // This excludes junk, deleted items, and other folders
            $response = $this->makeGraphRequest(
                'GET',
                '/me/mailFolders/inbox/messages',
                $accessToken,
                [
                    'filter' => "isRead eq false",
                    'orderby' => "receivedDateTime desc",
                    'top' => $limit,
                    'skip' => $offset,
                    'select' => "subject,from,receivedDateTime,bodyPreview,isRead,inferenceClassification"
                ]
            );
            
            $emails = $this->formatEmails($response);
            
            // Filter out emails classified as "Other" (focused inbox feature)
            $focusedEmails = array_filter($emails, function($email) {
                // Keep emails that are either 'focused' or don't have classification
                return !isset($email['inference_classification']) || 
                       $email['inference_classification'] !== 'other';
            });
            
            // Return the focused emails, limited to the requested amount
            $finalEmails = array_slice(array_values($focusedEmails), 0, $limit);
            
            // Add AI analysis if requested and available
            if ($includeAI && config('ai.enabled', true)) {
                $finalEmails = $this->addAIAnalysisToEmails($finalEmails);
            }
            
            // Cache the results
            $this->emailCacheService->cacheEmails($finalEmails);
            
            return $finalEmails;
            
        } catch (Exception $e) {
            // Fallback to the original approach with additional filtering
            try {
                $requestConfiguration = [
                    'filter' => "isRead eq false and parentFolderId ne 'junkemail'",
                    'orderby' => "receivedDateTime desc",
                    'top' => $limit * 2, // Get more to filter out "Other" emails
                    'select' => "subject,from,receivedDateTime,bodyPreview,isRead,inferenceClassification,parentFolderId"
                ];
                
                $response = $this->makeGraphRequest(
                    'GET',
                    '/me/messages',
                    $accessToken,
                    $requestConfiguration
                );
                
                $emails = $this->formatEmails($response);
                
                // Filter out "Other" emails and limit results
                $filteredEmails = array_filter($emails, function($email) {
                    return !isset($email['inference_classification']) || 
                           $email['inference_classification'] !== 'other';
                });
                
                $finalEmails = array_slice(array_values($filteredEmails), 0, $limit);
                
                // Add AI analysis if requested and available
                if ($includeAI && config('ai.enabled', true)) {
                    $finalEmails = $this->addAIAnalysisToEmails($finalEmails);
                }
                
                // Cache the results
                $this->emailCacheService->cacheEmails($finalEmails);
                
                return $finalEmails;
                
            } catch (Exception $e2) {
                throw new Exception('Failed to fetch emails: ' . $e->getMessage());
            }
        }
    }
    
    private function makeGraphRequest(string $method, string $endpoint, string $accessToken, array $params = [], string $jsonBody = null): array
    {
        $url = 'https://graph.microsoft.com/v1.0' . $endpoint;
        
        if (!empty($params)) {
            $queryString = http_build_query([
                '$filter' => $params['filter'] ?? null,
                '$orderby' => $params['orderby'] ?? null,
                '$top' => $params['top'] ?? null,
                '$skip' => $params['skip'] ?? null,
                '$select' => $params['select'] ?? null,
            ], '', '&', PHP_QUERY_RFC3986);
            
            // Remove empty parameters
            $queryString = preg_replace('/[^=&]*=(?:&|$)/', '', $queryString);
            $queryString = rtrim($queryString, '&');
            
            if (!empty($queryString)) {
                $url .= '?' . $queryString;
            }
        }
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Add JSON body for PATCH, POST, PUT requests
        if ($jsonBody && in_array($method, ['PATCH', 'POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Accept both 200 and 204 (No Content) as success
        if (!in_array($httpCode, [200, 204])) {
            throw new Exception('Graph API request failed with status: ' . $httpCode . ' Response: ' . $response);
        }
        
        // For DELETE requests or 204 responses, return empty array
        if ($httpCode === 204 || empty($response)) {
            return [];
        }
        
        return json_decode($response, true);
    }
    
    private function formatEmails(array $response): array
    {
        if (!isset($response['value'])) {
            return [];
        }
        
        $emails = [];
        foreach ($response['value'] as $email) {
            $emails[] = [
                'id' => $email['id'] ?? '',
                'subject' => $email['subject'] ?? 'No Subject',
                'from' => $email['from']['emailAddress']['name'] ?? 'Unknown Sender',
                'from_email' => $email['from']['emailAddress']['address'] ?? '',
                'received_date' => $email['receivedDateTime'] ?? '',
                'body_preview' => $email['bodyPreview'] ?? '',
                'is_read' => $email['isRead'] ?? false,
                'inference_classification' => $email['inferenceClassification'] ?? null,
            ];
        }
        
        return $emails;
    }
    
    public function getUserInfo(bool $forceRefresh = false): array
    {
        // Check cache first unless force refresh is requested
        if (!$forceRefresh && $this->emailCacheService->hasValidUserInfoCache()) {
            $cachedData = $this->emailCacheService->getCachedUserInfo();
            return $cachedData['user_info'];
        }
        
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }
        
        try {
            $response = $this->makeGraphRequest('GET', '/me', $accessToken);
            
            $userInfo = [
                'display_name' => $response['displayName'] ?? '',
                'email' => $response['mail'] ?? $response['userPrincipalName'] ?? '',
                'id' => $response['id'] ?? '',
                'user_type' => $response['userType'] ?? 'unknown',
                'account_enabled' => $response['accountEnabled'] ?? false,
            ];
            
            // Cache the user info
            $this->emailCacheService->cacheUserInfo($userInfo);
            
            return $userInfo;
        } catch (Exception $e) {
            throw new Exception('Failed to fetch user info: ' . $e->getMessage());
        }
    }
    
    /**
     * Get cache service instance for external use
     */
    public function getCacheService(): EmailCacheService
    {
        return $this->emailCacheService;
    }
    
    /**
     * Add AI analysis to emails
     */
    private function addAIAnalysisToEmails(array $emails): array
    {
        if (!$this->aiService->isAvailable()) {
            return $emails;
        }
        
        $analyzedEmails = [];
        
        foreach ($emails as $email) {
            // Check if AI analysis is already cached
            if ($this->emailCacheService->hasAIAnalysis($email)) {
                $cachedAnalysis = $this->emailCacheService->getAIAnalysis($email);
                $email['ai_analysis'] = $cachedAnalysis['analysis'];
                $email['ai_cached_at'] = $cachedAnalysis['cached_at'];
            } else {
                // Generate new AI analysis
                try {
                    $analysis = $this->aiService->analyzeEmail($email);
                    $email['ai_analysis'] = $analysis;
                    $email['ai_cached_at'] = now()->toISOString();
                    
                    // Cache the analysis
                    $this->emailCacheService->cacheAIAnalysis($email, $analysis);
                } catch (Exception $e) {
                    // If AI analysis fails, add a fallback
                    $email['ai_analysis'] = [
                        'summary' => 'AI analysis unavailable',
                        'priority' => 'medium',
                        'category' => 'personal',
                        'action_items' => [],
                        'sentiment' => 'neutral',
                        'requires_response' => false,
                        'error' => true
                    ];
                }
            }
            
            $analyzedEmails[] = $email;
        }
        
        return $analyzedEmails;
    }
    
    /**
     * Get AI service instance for external use
     */
    public function getAIService(): AIService
    {
        return $this->aiService;
    }

    /**
     * Get total count of unread emails in inbox
     */
    public function getTotalEmailCount(): int
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            $response = $this->makeGraphRequest(
                'GET',
                '/me/mailFolders/inbox/messages/$count',
                $accessToken,
                [
                    'filter' => "isRead eq false"
                ]
            );

            // The response should be a simple number
            return (int) $response;

        } catch (Exception $e) {
            // Fallback: return 0 if count fails
            return 0;
        }
    }

    /**
     * Mark an email as read
     */
    public function markAsRead(string $emailId): bool
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            $this->makeGraphRequest(
                'PATCH',
                "/me/messages/{$emailId}",
                $accessToken,
                [],
                json_encode(['isRead' => true])
            );

            // Clear cache to force refresh
            $this->emailCacheService->clearEmailCache();

            return true;

        } catch (Exception $e) {
            throw new Exception('Failed to mark email as read: ' . $e->getMessage());
        }
    }

    /**
     * Mark an email as unread
     */
    public function markAsUnread(string $emailId): bool
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            $this->makeGraphRequest(
                'PATCH',
                "/me/messages/{$emailId}",
                $accessToken,
                [],
                json_encode(['isRead' => false])
            );

            // Clear cache to force refresh
            $this->emailCacheService->clearEmailCache();

            return true;

        } catch (Exception $e) {
            throw new Exception('Failed to mark email as unread: ' . $e->getMessage());
        }
    }

    /**
     * Mark an email as important
     */
    public function markAsImportant(string $emailId): bool
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            $this->makeGraphRequest(
                'PATCH',
                "/me/messages/{$emailId}",
                $accessToken,
                [],
                json_encode(['importance' => 'high'])
            );

            return true;

        } catch (Exception $e) {
            throw new Exception('Failed to mark email as important: ' . $e->getMessage());
        }
    }

    /**
     * Archive an email (move to Archive folder)
     */
    public function archiveEmail(string $emailId): bool
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            // First, get the Archive folder ID
            $foldersResponse = $this->makeGraphRequest(
                'GET',
                '/me/mailFolders',
                $accessToken
            );

            $archiveFolderId = null;
            foreach ($foldersResponse['value'] as $folder) {
                if ($folder['displayName'] === 'Archive' || $folder['wellKnownName'] === 'archive') {
                    $archiveFolderId = $folder['id'];
                    break;
                }
            }

            if (!$archiveFolderId) {
                throw new Exception('Archive folder not found');
            }

            // Move the email to Archive folder
            $this->makeGraphRequest(
                'POST',
                "/me/messages/{$emailId}/move",
                $accessToken,
                [],
                json_encode(['destinationId' => $archiveFolderId])
            );

            // Clear cache to force refresh
            $this->emailCacheService->clearEmailCache();

            return true;

        } catch (Exception $e) {
            throw new Exception('Failed to archive email: ' . $e->getMessage());
        }
    }

    /**
     * Delete an email
     */
    public function deleteEmail(string $emailId): bool
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            $this->makeGraphRequest(
                'DELETE',
                "/me/messages/{$emailId}",
                $accessToken
            );

            // Clear cache to force refresh
            $this->emailCacheService->clearEmailCache();

            return true;

        } catch (Exception $e) {
            throw new Exception('Failed to delete email: ' . $e->getMessage());
        }
    }

    /**
     * Get detailed email information including full body content
     */
    public function getEmailDetails(string $emailId): array
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }

        try {
            $response = $this->makeGraphRequest(
                'GET',
                "/me/messages/{$emailId}",
                $accessToken,
                [
                    'select' => 'id,subject,from,toRecipients,receivedDateTime,body,bodyPreview,isRead,importance,inferenceClassification,hasAttachments,attachments'
                ]
            );

            // Format the detailed email data
            $emailDetails = [
                'id' => $response['id'] ?? '',
                'subject' => $response['subject'] ?? 'No Subject',
                'from' => $response['from']['emailAddress']['name'] ?? 'Unknown Sender',
                'from_email' => $response['from']['emailAddress']['address'] ?? '',
                'to_recipients' => [],
                'received_date' => $response['receivedDateTime'] ?? '',
                'body_content' => $response['body']['content'] ?? '',
                'body_type' => $response['body']['contentType'] ?? 'text',
                'body_preview' => $response['bodyPreview'] ?? '',
                'is_read' => $response['isRead'] ?? false,
                'importance' => $response['importance'] ?? 'normal',
                'inference_classification' => $response['inferenceClassification'] ?? null,
                'has_attachments' => $response['hasAttachments'] ?? false,
                'attachments' => []
            ];

            // Format recipients
            if (isset($response['toRecipients'])) {
                foreach ($response['toRecipients'] as $recipient) {
                    $emailDetails['to_recipients'][] = [
                        'name' => $recipient['emailAddress']['name'] ?? '',
                        'email' => $recipient['emailAddress']['address'] ?? ''
                    ];
                }
            }

            // Get attachments if they exist
            if ($emailDetails['has_attachments']) {
                try {
                    $attachmentsResponse = $this->makeGraphRequest(
                        'GET',
                        "/me/messages/{$emailId}/attachments",
                        $accessToken,
                        [
                            'select' => 'id,name,contentType,size,isInline'
                        ]
                    );

                    if (isset($attachmentsResponse['value'])) {
                        foreach ($attachmentsResponse['value'] as $attachment) {
                            $emailDetails['attachments'][] = [
                                'id' => $attachment['id'] ?? '',
                                'name' => $attachment['name'] ?? 'Unknown',
                                'content_type' => $attachment['contentType'] ?? '',
                                'size' => $attachment['size'] ?? 0,
                                'is_inline' => $attachment['isInline'] ?? false
                            ];
                        }
                    }
                } catch (Exception $e) {
                    // Attachments failed to load, but continue with email details
                    error_log('Failed to load attachments for email ' . $emailId . ': ' . $e->getMessage());
                }
            }

            return $emailDetails;

        } catch (Exception $e) {
            throw new Exception('Failed to get email details: ' . $e->getMessage());
        }
    }
}
