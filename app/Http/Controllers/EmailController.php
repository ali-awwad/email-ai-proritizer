<?php

namespace App\Http\Controllers;

use App\Services\GraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class EmailController extends Controller
{
    private $graphService;
    
    public function __construct(GraphService $graphService)
    {
        $this->graphService = $graphService;
    }
    
    public function index()
    {
        try {
            if (!$this->graphService->isAuthenticated()) {
                // Show the login page instead of redirecting
                return view('emails.index', [
                    'emails' => [], 
                    'userInfo' => null, 
                    'cacheStats' => null
                ]);
            }
            
            // Get user info and emails
            $userInfo = $this->graphService->getUserInfo();
            
            // Try to get emails
            try {
                $emails = $this->graphService->getUnreadEmails(5);
            } catch (Exception $emailError) {
                // If emails fail, still show the page with user info and error
                $emails = [];
            }
            
            // Get cache statistics
            $cacheStats = $this->graphService->getCacheService()->getCacheStats();
            
            return view('emails.index', compact('emails', 'userInfo', 'cacheStats'));
            
        } catch (Exception $e) {
            // Clear authentication and show login page instead of redirect
            $this->graphService->clearAuthentication();
            return view('emails.index', [
                'emails' => [], 
                'userInfo' => null, 
                'cacheStats' => null
            ])->with('error', 'Authentication expired. Please sign in again.');
        }
    }
    
    public function authenticate()
    {
        try {
            $authUrl = $this->graphService->getAuthorizationUrl();
            return redirect($authUrl);
        } catch (Exception $e) {
            return redirect()->route('emails.index')
                ->with('error', 'Failed to initiate authentication: ' . $e->getMessage());
        }
    }
    
    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');
        $errorDescription = $request->get('error_description');
        
        if ($error) {
            return redirect()->route('emails.index')
                ->with('error', 'Authentication failed: ' . $error . ($errorDescription ? ' - ' . $errorDescription : ''));
        }
        
        if (!$code) {
            return redirect()->route('emails.index')
                ->with('error', 'No authorization code received.');
        }
        
        try {
            $success = $this->graphService->handleCallback($code, $state);
            
            if ($success) {
                return redirect()->route('emails.index')
                    ->with('success', 'Successfully authenticated with Microsoft Graph!');
            } else {
                return redirect()->route('emails.index')
                    ->with('error', 'Failed to authenticate with Microsoft Graph.');
            }
            
        } catch (Exception $e) {
            return redirect()->route('emails.index')
                ->with('error', 'Authentication error: ' . $e->getMessage());
        }
    }
    
    public function logout()
    {
        $this->graphService->clearAuthentication();
        
        return redirect()->route('emails.index')
            ->with('success', 'Successfully logged out.');
    }
    
    public function refresh()
    {
        try {
            if (!$this->graphService->isAuthenticated()) {
                return redirect()->route('auth.microsoft')
                    ->with('error', 'Please authenticate first.');
            }
            
            // Force refresh from API (bypass cache)
            $emails = $this->graphService->getUnreadEmails(5, true);
            $cacheStats = $this->graphService->getCacheService()->getCacheStats();
            
            return response()->json([
                'success' => true,
                'emails' => $emails,
                'count' => count($emails),
                'cache_stats' => $cacheStats
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Clear all cache for the current user
     */
    public function clearCache()
    {
        try {
            if (!$this->graphService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not authenticated'
                ], 401);
            }
            
            $this->graphService->getCacheService()->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function cacheStats()
    {
        try {
            if (!$this->graphService->isAuthenticated()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Not authenticated'
                ], 401);
            }
            
            $cacheStats = $this->graphService->getCacheService()->getCacheStats();
            
            return response()->json([
                'success' => true,
                'cache_stats' => $cacheStats
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
