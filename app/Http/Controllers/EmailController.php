<?php

namespace App\Http\Controllers;

use App\Services\GraphService;
use App\Services\AIService;
use Illuminate\Http\Request;
use Exception;

class EmailController extends Controller
{
    private $graphService;
    private $aiService;
    
    public function __construct(GraphService $graphService, AIService $aiService)
    {
        $this->graphService = $graphService;
        $this->aiService = $aiService;
    }
    
    public function index()
    {
        try {
            if (!$this->graphService->isAuthenticated()) {
                return view('emails.index', [
                    'emails' => [], 
                    'userInfo' => null
                ]);
            }
            
            // Get user info
            $userInfo = $this->graphService->getUserInfo();

            // Get last 20 unread emails from inbox only
            $emails = $this->graphService->getUnreadEmails(20, 0);

            // Process each email with AI analysis
            foreach ($emails as &$email) {
                try {
                    // Get AI analysis for each email
                    $email['ai_analysis'] = $this->aiService->analyzeEmail($email);
                } catch (Exception $e) {
                    // If AI analysis fails, continue without it
                    $email['ai_analysis'] = [
                        'summary' => 'AI analysis unavailable',
                        'priority' => 'medium',
                        'category' => 'personal',
                        'requires_response' => false,
                        'sentiment' => 'neutral',
                        'action_items' => [],
                        'sender_authority' => 'medium',
                        'sender_title' => ''
                    ];
                }
            }

            // Generate daily summary
            $dailySummary = $this->aiService->generateDailySummary($emails);
            
            return view('emails.index', [
                'emails' => $emails,
                'userInfo' => $userInfo,
                'dailySummary' => $dailySummary
            ]);
            
        } catch (Exception $e) {
            return view('emails.index', [
                'emails' => [],
                'userInfo' => null,
                'dailySummary' => [
                    'total_emails' => 0,
                    'summary_text' => 'Unable to load emails.',
                    'prioritized_items' => []
                ],
                'error' => 'Failed to load emails: ' . $e->getMessage()
            ]);
        }
    }
    
    public function authenticate()
    {
        $authUrl = $this->graphService->getAuthorizationUrl();
        return redirect($authUrl);
    }
    
    public function callback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            
            if (!$code) {
                return redirect()->route('emails.index')->with('error', 'Authorization failed');
            }
            
            $success = $this->graphService->handleCallback($code, $state);
            
            if ($success) {
                return redirect()->route('emails.index')->with('success', 'Authentication successful');
            } else {
                return redirect()->route('emails.index')->with('error', 'Authentication failed');
            }
        } catch (Exception $e) {
            return redirect()->route('emails.index')->with('error', 'Authentication error: ' . $e->getMessage());
        }
    }
    
    public function logout(Request $request)
    {
        $this->graphService->clearAuthentication();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('emails.index')->with('success', 'Logged out successfully');
    }
}
