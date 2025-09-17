# WOW Database - Microsoft Graph Email Integration

## Project Overview
This Laravel application integrates with Microsoft Graph API to fetch and display unread emails from a user's inbox. The project demonstrates OAuth 2.0 authentication flow and real-time email retrieval with a clean, responsive Bootstrap UI.

## Tech Stack
- **Framework**: Laravel 12
- **Authentication**: Microsoft Graph OAuth 2.0 (Consumer/Personal accounts)
- **Frontend**: Bootstrap 5, Blade templates
- **API Integration**: Microsoft Graph SDK for PHP
- **Local Development**: Laravel Valet (`wow-database.test`)
- **Dependencies**: `microsoft/microsoft-graph`, `league/oauth2-client`

## Architecture

### Core Components
1. **GraphService** (`app/Services/GraphService.php`)
   - Handles OAuth 2.0 authentication flow
   - Manages access token lifecycle with refresh capabilities
   - Fetches emails from Microsoft Graph API
   - Filters emails to inbox only (excludes junk and "Other" folder)

2. **EmailController** (`app/Http/Controllers/EmailController.php`)
   - Manages authentication routes (`/auth`, `/callback`)
   - Displays email interface (`/emails`)
   - Handles authentication errors gracefully

3. **Configuration** (`config/graph.php`)
   - Microsoft Graph API settings
   - OAuth scopes: `Mail.Read`, `offline_access`
   - Consumer-specific endpoints for personal Microsoft accounts

4. **UI Views** (`resources/views/emails/index.blade.php`)
   - Bootstrap 5 responsive design
   - Real-time refresh capabilities
   - Visual email classification with badges
   - Authentication flow integration

### Key Features
- ✅ OAuth 2.0 authentication with personal Microsoft accounts
- ✅ Fetches last 5 unread emails from inbox only
- ✅ Excludes junk mail and "Other" folder emails
- ✅ Responsive web interface with Bootstrap 5
- ✅ Token refresh handling for long-term sessions
- ✅ Production-ready code (debugging removed)

## Setup Instructions

### Prerequisites
1. Microsoft Azure App Registration (Personal accounts only)
2. Laravel Valet configured for local development
3. Composer dependencies installed

### Environment Configuration
```env
GRAPH_CLIENT_ID=your_azure_app_client_id
GRAPH_CLIENT_SECRET=your_azure_app_client_secret
GRAPH_REDIRECT_URI=https://wow-database.test/callback
```

### Azure App Registration Settings
- **Account Types**: Personal Microsoft accounts only
- **Redirect URI**: `https://wow-database.test/callback`
- **Required Permissions**: `Mail.Read`, `offline_access`

### Installation
```bash
composer install
cp .env.example .env
# Configure GRAPH_* variables in .env
php artisan key:generate
```

## Usage
1. Visit `https://wow-database.test/emails`
2. Click "Authenticate with Microsoft"
3. Sign in with personal Microsoft account
4. View last 5 unread inbox emails

## Completed Development Tasks

### ✅ Phase 1: Initial Setup
- [x] Install Microsoft Graph SDK (`microsoft/microsoft-graph`)
- [x] Create environment configuration variables
- [x] Set up Graph API configuration file

### ✅ Phase 2: Core Implementation
- [x] Build GraphService for OAuth 2.0 authentication
- [x] Implement token management with refresh capabilities
- [x] Create EmailController for route handling
- [x] Build responsive UI with Bootstrap 5

### ✅ Phase 3: Authentication Debugging
- [x] Resolve OAuth redirect loop issues
- [x] Fix Azure app registration configuration
- [x] Switch from `/common/` to `/consumers/` endpoints
- [x] Handle "MailboxNotEnabledForRESTAPI" errors

### ✅ Phase 4: Email Filtering
- [x] Filter emails to inbox only (exclude junk)
- [x] Exclude "Other" folder emails from results
- [x] Implement proper email source classification

### ✅ Phase 5: Production Cleanup
- [x] Remove all debugging logs and statements
- [x] Clean up routes and controller code
- [x] Prepare production-ready codebase

## Future Development Roadmap

### 🔄 Phase 6: Caching Implementation
- [ ] **Store emails in Cache**
  - Implement Laravel Cache for email storage
  - Add cache invalidation strategies
  - Optimize API call frequency
  - Consider Redis for session-based caching

### 🤖 Phase 7: AI Email Analysis
- [ ] **AI Service Integration**
  - Research free AI APIs (OpenAI GPT-3.5-turbo has free tier)
  - Alternative: Hugging Face Transformers (free tier available)
  - Consider: Google Gemini API (has free quota)
  - Local option: Ollama with lightweight models

- [ ] **Email Summarization Service**
  - Create AIService class for text analysis
  - Implement email content summarization
  - Add email priority classification (High/Medium/Low)
  - Extract key action items from emails

### 📊 Phase 8: Enhanced Email Display
- [ ] **Prioritized Email View**
  - Create blade view for summarized emails
  - Display priority badges (🔴 High, 🟡 Medium, 🟢 Low)
  - Show AI-generated summaries
  - Add action item extraction
  - Implement email categorization (Work/Personal/Newsletters)

### 🚀 Phase 9: Advanced Features
- [ ] Email marking as read/unread
- [ ] Pagination for large email volumes
- [ ] Search and filter capabilities
- [ ] Email composition and sending
- [ ] Attachment handling
- [ ] Multi-account support

## Free AI Service Recommendations

### Option 1: OpenAI GPT-3.5-turbo
- **Cost**: $0.0015 per 1K input tokens, $0.002 per 1K output tokens
- **Free Tier**: $5 credit for new accounts
- **Pros**: Excellent summarization, reliable API
- **Integration**: Easy with `openai-php/client`

### Option 2: Hugging Face Transformers
- **Cost**: Free tier available (limited requests)
- **Models**: facebook/bart-large-cnn for summarization
- **Pros**: Multiple models, good free tier
- **Integration**: REST API or transformers library

### Option 3: Google Gemini API
- **Cost**: Free tier with rate limits
- **Pros**: Good performance, generous free quota
- **Integration**: Google AI SDK

### Option 4: Ollama (Local)
- **Cost**: Completely free (local processing)
- **Models**: llama3.2:1b, phi3:mini (lightweight)
- **Pros**: No API costs, privacy, offline capability
- **Cons**: Requires local setup, slower processing

## Development Notes
- Authentication requires personal Microsoft accounts only
- Use `/consumers/` OAuth endpoints for personal accounts
- Email filtering logic excludes junk and "Other" folder
- Bootstrap 5 provides responsive design
- Session-based token storage (consider database for production)

## Contributing
When working on this project:
1. Test authentication flow thoroughly
2. Validate email filtering logic
3. Maintain responsive design principles
4. Follow Laravel coding standards
5. Update this documentation for new features