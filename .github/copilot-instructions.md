# WOW Database - Daily Email Summary

## Project Overview
This Laravel application integrates with Microsoft Graph API to fetch the last 5 unread emails from a user's inbox and provides AI-powered daily summaries to help users start their day efficiently. The application focuses purely on read-only email analysis and prioritization.

## Core Purpose
- **Fetch**: Last 5 unread emails from inbox (excludes junk and "Other" folder)
- **Analyze**: AI-powered analysis including summaries, priorities, and action items
- **Display**: Clean, focused daily summary interface
- **Goal**: Help users quickly understand their most important emails each morning

## Tech Stack
- **Framework**: Laravel 12
- **Authentication**: Microsoft Graph OAuth 2.0 (Consumer/Personal accounts)
- **Frontend**: Bootstrap 5, minimal responsive design
- **API Integration**: Microsoft Graph SDK for PHP
- **AI Service**: OpenAI GPT-3.5-turbo for email analysis
- **Local Development**: Laravel Valet (`wow-database.test`)
- **Dependencies**: `microsoft/microsoft-graph`, `league/oauth2-client`, OpenAI PHP client

## Architecture

### Core Components
1. **GraphService** (`app/Services/GraphService.php`)
   - Handles OAuth 2.0 authentication flow
   - Fetches exactly 5 unread emails from inbox only
   - Filters out junk and "Other" folder emails

2. **AIService** (`app/Services/AIService.php`)
   - Analyzes email content with OpenAI GPT-3.5-turbo
   - Provides summaries, priority classification, sentiment analysis
   - Extracts action items and categorizes emails

3. **EmailController** (`app/Http/Controllers/EmailController.php`)
   - Simple controller with only essential routes
   - Combines email fetching with AI analysis
   - Displays unified daily summary

4. **Daily Summary View** (`resources/views/emails/index.blade.php`)
   - Clean, focused interface showing 5 emails max
   - AI-powered summaries and priorities
   - No editing/management features - read-only

### Key Features
- ✅ OAuth 2.0 authentication with personal Microsoft accounts
- ✅ Fetches exactly 5 unread emails from inbox only
- ✅ AI-powered email analysis and summarization
- ✅ Priority classification (High/Medium/Low)
- ✅ Action item extraction
- ✅ Clean, minimal daily summary interface
- ✅ Read-only design - no email management features

## Current Implementation Status

### ✅ Completed Features
- [x] Microsoft Graph OAuth 2.0 integration
- [x] Inbox email filtering (excludes junk/other)
- [x] AI service integration with OpenAI GPT-3.5-turbo
- [x] Email analysis with summaries and priorities
- [x] Daily summary interface design
- [x] Simplified controller and routes
- [x] Production-ready authentication flow

### 🎯 Project Goals
This project is **feature-complete** for its intended purpose:
1. **Simple Daily Workflow**: Users visit the site each morning
2. **Quick Authentication**: Sign in with Microsoft account
3. **Instant Summary**: View AI-analyzed summary of 5 most recent unread emails
4. **Actionable Insights**: See priorities, summaries, and action items
5. **Start Day Informed**: Users can quickly understand what needs attention

## Setup Instructions

### Prerequisites
1. Microsoft Azure App Registration (Personal accounts only)
2. OpenAI API key for GPT-3.5-turbo
3. Laravel Valet configured for local development

### Environment Configuration
```env
GRAPH_CLIENT_ID=your_azure_app_client_id
GRAPH_CLIENT_SECRET=your_azure_app_client_secret
GRAPH_REDIRECT_URI=https://wow-database.test/callback
OPENAI_API_KEY=your_openai_api_key
```

### Azure App Registration Settings
- **Account Types**: Personal Microsoft accounts only
- **Redirect URI**: `https://wow-database.test/callback`
- **Required Permissions**: `Mail.Read`, `offline_access`

### Installation
```bash
composer install
cp .env.example .env
# Configure GRAPH_* and OPENAI_* variables in .env
php artisan key:generate
```

## Usage
1. Visit `https://wow-database.test/emails`
2. Click "Sign in with Microsoft"
3. Sign in with personal Microsoft account
4. View AI-powered daily email summary

## Application Flow
```
User visits /emails
    ↓
Authentication check
    ↓
Fetch 5 unread inbox emails (via Microsoft Graph)
    ↓
AI analysis of each email (via OpenAI)
    ↓
Display unified daily summary with:
    - Email count and priority distribution
    - Individual email cards with AI summaries
    - Priority badges and action items
    - Response requirements and sentiment
```

## File Structure (Simplified)
```
├── app/Http/Controllers/EmailController.php    # Simple controller (4 methods only)
├── app/Services/GraphService.php               # Microsoft Graph integration
├── app/Services/AIService.php                  # OpenAI email analysis
├── resources/views/emails/index.blade.php     # Daily summary view
├── routes/web.php                              # Minimal route definitions
└── config/graph.php                           # Graph API configuration
```

## Development Philosophy
- **Simplicity**: No unnecessary features or complexity
- **Focus**: Single purpose - daily email summary
- **Efficiency**: Fast loading, minimal UI, clear information
- **Reliability**: Robust error handling, graceful AI fallbacks
- **Privacy**: Read-only access, no data storage beyond session

## Future Considerations (Optional)
If expanding beyond the core purpose:
- [ ] Cache email analysis to reduce API calls
- [ ] Add time-based filtering (emails from last 24 hours)
- [ ] Export daily summary as PDF
- [ ] Email delivery of daily summaries
- [ ] Multiple account support

## Development Notes
- Keep the interface minimal and focused
- Maintain read-only philosophy
- Ensure AI analysis gracefully degrades if unavailable
- Test with various email types and volumes
- Optimize for morning routine use case