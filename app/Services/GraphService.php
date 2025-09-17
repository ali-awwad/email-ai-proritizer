<?php

namespace App\Services;

use League\OAuth2\Client\Provider\GenericProvider;
use Exception;

class GraphService
{
    private $provider;
    
    public function __construct()
    {
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
        session()->forget(['graph_access_token', 'graph_refresh_token', 'graph_expires']);
    }
    
    public function getUnreadEmails(int $limit = 5): array
    {
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
            return array_slice(array_values($focusedEmails), 0, $limit);
            
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
                
                return array_slice(array_values($filteredEmails), 0, $limit);
                
            } catch (Exception $e2) {
                throw new Exception('Failed to fetch emails: ' . $e->getMessage());
            }
        }
    }
    
    private function makeGraphRequest(string $method, string $endpoint, string $accessToken, array $params = []): array
    {
        $url = 'https://graph.microsoft.com/v1.0' . $endpoint;
        
        if (!empty($params)) {
            $queryString = http_build_query([
                '$filter' => $params['filter'] ?? null,
                '$orderby' => $params['orderby'] ?? null,
                '$top' => $params['top'] ?? null,
                '$select' => $params['select'] ?? null,
            ]);
            $queryString = preg_replace('/%24/', '$', $queryString); // Fix encoded $ signs
            $url .= '?' . $queryString;
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
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Graph API request failed with status: ' . $httpCode . ' Response: ' . $response);
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
    
    public function getUserInfo(): array
    {
        $accessToken = $this->getValidAccessToken();
        
        if (!$accessToken) {
            throw new Exception('Not authenticated with Microsoft Graph');
        }
        
        try {
            $response = $this->makeGraphRequest('GET', '/me', $accessToken);
            
            return [
                'display_name' => $response['displayName'] ?? '',
                'email' => $response['mail'] ?? $response['userPrincipalName'] ?? '',
                'id' => $response['id'] ?? '',
                'user_type' => $response['userType'] ?? 'unknown',
                'account_enabled' => $response['accountEnabled'] ?? false,
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to fetch user info: ' . $e->getMessage());
        }
    }
}
