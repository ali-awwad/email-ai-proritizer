<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Exception;

class AIService
{
    private const MODEL = 'gpt-3.5-turbo';
    private const MAX_TOKENS = 150;
    private const TEMPERATURE = 0.3;
    
    /**
     * Analyze an email and return AI-generated insights
     */
    public function analyzeEmail(array $email): array
    {
        try {
            $prompt = $this->buildEmailAnalysisPrompt($email);
            
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI email assistant that analyzes emails and provides concise summaries, priority levels, and action items. Always respond in valid JSON format.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => self::MAX_TOKENS,
                'temperature' => self::TEMPERATURE,
                'response_format' => ['type' => 'json_object']
            ]);
            
            $content = $response->choices[0]->message->content;
            $analysis = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from AI');
            }
            
            return $this->validateAndNormalizeAnalysis($analysis);
            
        } catch (Exception $e) {
            Log::warning('AI analysis failed', [
                'email_subject' => $email['subject'] ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            
            // Return fallback analysis
            return $this->getFallbackAnalysis($email);
        }
    }
    
    /**
     * Build the prompt for email analysis
     */
    private function buildEmailAnalysisPrompt(array $email): string
    {
        $subject = $email['subject'] ?? 'No Subject';
        $from = $email['from'] ?? 'Unknown Sender';
        $preview = $email['body_preview'] ?? '';
        
        return "Analyze this email and provide a JSON response with the following structure:
{
    \"summary\": \"Brief 1-2 sentence summary\",
    \"priority\": \"high|medium|low\",
    \"category\": \"work|personal|newsletter|promotional|support\",
    \"action_items\": [\"action1\", \"action2\"],
    \"sentiment\": \"positive|neutral|negative\",
    \"requires_response\": true|false
}

Email details:
Subject: {$subject}
From: {$from}
Content: {$preview}

Focus on extracting key information and determining if this email requires immediate attention or action.";
    }
    
    /**
     * Validate and normalize AI analysis response
     */
    private function validateAndNormalizeAnalysis(array $analysis): array
    {
        $defaults = [
            'summary' => 'No summary available',
            'priority' => 'medium',
            'category' => 'personal',
            'action_items' => [],
            'sentiment' => 'neutral',
            'requires_response' => false
        ];
        
        // Merge with defaults to ensure all keys exist
        $analysis = array_merge($defaults, $analysis);
        
        // Validate priority
        if (!in_array($analysis['priority'], ['high', 'medium', 'low'])) {
            $analysis['priority'] = 'medium';
        }
        
        // Validate category
        $validCategories = ['work', 'personal', 'newsletter', 'promotional', 'support'];
        if (!in_array($analysis['category'], $validCategories)) {
            $analysis['category'] = 'personal';
        }
        
        // Validate sentiment
        if (!in_array($analysis['sentiment'], ['positive', 'neutral', 'negative'])) {
            $analysis['sentiment'] = 'neutral';
        }
        
        // Ensure action_items is an array
        if (!is_array($analysis['action_items'])) {
            $analysis['action_items'] = [];
        }
        
        // Limit action items to 3
        $analysis['action_items'] = array_slice($analysis['action_items'], 0, 3);
        
        // Ensure requires_response is boolean
        $analysis['requires_response'] = (bool) $analysis['requires_response'];
        
        return $analysis;
    }
    
    /**
     * Get fallback analysis when AI fails
     */
    private function getFallbackAnalysis(array $email): array
    {
        $subject = strtolower($email['subject'] ?? '');
        $preview = strtolower($email['body_preview'] ?? '');
        
        // Simple keyword-based priority detection
        $priority = 'medium';
        $urgentKeywords = ['urgent', 'asap', 'emergency', 'critical', 'important', 'deadline'];
        $lowKeywords = ['newsletter', 'unsubscribe', 'promotion', 'deal', 'offer'];
        
        foreach ($urgentKeywords as $keyword) {
            if (strpos($subject . ' ' . $preview, $keyword) !== false) {
                $priority = 'high';
                break;
            }
        }
        
        foreach ($lowKeywords as $keyword) {
            if (strpos($subject . ' ' . $preview, $keyword) !== false) {
                $priority = 'low';
                break;
            }
        }
        
        // Simple category detection
        $category = 'personal';
        if (strpos($subject . ' ' . $preview, 'newsletter') !== false || 
            strpos($subject . ' ' . $preview, 'unsubscribe') !== false) {
            $category = 'newsletter';
        } elseif (strpos($subject . ' ' . $preview, 'work') !== false || 
                  strpos($subject . ' ' . $preview, 'meeting') !== false) {
            $category = 'work';
        }
        
        return [
            'summary' => 'AI analysis unavailable - using basic classification',
            'priority' => $priority,
            'category' => $category,
            'action_items' => [],
            'sentiment' => 'neutral',
            'requires_response' => $priority === 'high',
            'ai_analysis' => false // Flag to indicate this is fallback
        ];
    }
    
    /**
     * Batch analyze multiple emails
     */
    public function analyzeEmails(array $emails): array
    {
        $analyzedEmails = [];
        
        foreach ($emails as $email) {
            $analysis = $this->analyzeEmail($email);
            $analyzedEmails[] = array_merge($email, [
                'ai_analysis' => $analysis,
                'analyzed_at' => now()->toISOString()
            ]);
        }
        
        return $analyzedEmails;
    }
    
    /**
     * Get summary statistics for analyzed emails
     */
    public function getAnalysisStats(array $analyzedEmails): array
    {
        $stats = [
            'total' => count($analyzedEmails),
            'priority' => ['high' => 0, 'medium' => 0, 'low' => 0],
            'category' => [],
            'requires_response' => 0,
            'sentiment' => ['positive' => 0, 'neutral' => 0, 'negative' => 0]
        ];
        
        foreach ($analyzedEmails as $email) {
            $analysis = $email['ai_analysis'] ?? [];
            
            // Count priorities
            $priority = $analysis['priority'] ?? 'medium';
            if (isset($stats['priority'][$priority])) {
                $stats['priority'][$priority]++;
            }
            
            // Count categories
            $category = $analysis['category'] ?? 'personal';
            $stats['category'][$category] = ($stats['category'][$category] ?? 0) + 1;
            
            // Count response required
            if ($analysis['requires_response'] ?? false) {
                $stats['requires_response']++;
            }
            
            // Count sentiment
            $sentiment = $analysis['sentiment'] ?? 'neutral';
            if (isset($stats['sentiment'][$sentiment])) {
                $stats['sentiment'][$sentiment]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        try {
            $apiKey = config('openai.api_key');
            return !empty($apiKey);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test AI service connection
     */
    public function testConnection(): array
    {
        try {
            if (!$this->isAvailable()) {
                return [
                    'success' => false,
                    'message' => 'OpenAI API key not configured'
                ];
            }
            
            $response = OpenAI::chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test connection. Respond with just "OK".'
                    ]
                ],
                'max_tokens' => 5
            ]);
            
            return [
                'success' => true,
                'message' => 'AI service connection successful',
                'response' => $response->choices[0]->message->content
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'AI service connection failed: ' . $e->getMessage()
            ];
        }
    }
}
