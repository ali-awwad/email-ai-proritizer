# Microsoft Graph Email Reader - Laravel Proof of Concept

This Laravel application demonstrates how to integrate with Microsoft Graph API to read the last 5 unread emails from a user's mailbox.

## Features

- OAuth 2.0 authentication with Microsoft Graph
- Fetch last 5 unread emails
- Beautiful Bootstrap-based UI
- Real-time email refresh functionality
- Secure token management with refresh capability

## Setup Instructions

### 1. Azure App Registration

Before you can use this application, you need to create an app registration in Azure:

1. Go to the [Azure Portal](https://portal.azure.com)
2. Navigate to **Azure Active Directory** > **App registrations**
3. Click **New registration**
4. Fill in the following details:
   - **Name**: WOW Database Email Reader (or any name you prefer)
   - **Supported account types**: Accounts in this organizational directory only (Single tenant)
   - **Redirect URI**: Web - `http://localhost:8000/auth/microsoft/callback`
5. Click **Register**

### 2. Configure API Permissions

1. In your app registration, go to **API permissions**
2. Click **Add a permission**
3. Select **Microsoft Graph**
4. Choose **Delegated permissions**
5. Add the following permissions:
   - `Mail.Read`
   - `User.Read`
6. Click **Grant admin consent** (if you have admin privileges)

### 3. Create Client Secret

1. Go to **Certificates & secrets**
2. Click **New client secret**
3. Add a description and set expiration
4. Click **Add**
5. **Copy the secret value immediately** (you won't be able to see it again)

### 4. Environment Configuration

1. Copy the `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Update your `.env` file with the Azure app registration details:
   ```env
   MICROSOFT_GRAPH_CLIENT_ID=your_client_id_from_azure
   MICROSOFT_GRAPH_CLIENT_SECRET=your_client_secret_from_azure
   MICROSOFT_GRAPH_TENANT_ID=your_tenant_id_from_azure
   MICROSOFT_GRAPH_REDIRECT_URI=http://localhost:8000/auth/microsoft/callback
   ```

3. Generate application key:
   ```bash
   php artisan key:generate
   ```

### 5. Install Dependencies

Make sure you have installed all dependencies:
```bash
composer install
```

### 6. Database Setup

Run the migrations to set up the database:
```bash
php artisan migrate
```

### 7. Start the Application

Start the Laravel development server:
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`.

## Usage

1. Visit `http://localhost:8000`
2. Click on "Microsoft Graph Email Reader" link
3. Click "Sign in with Microsoft" 
4. Authenticate with your Microsoft account
5. Grant permissions to read your emails
6. View your last 5 unread emails
7. Use the refresh button to get updated emails

## Application Structure

### Key Files

- `app/Services/GraphService.php` - Handles Microsoft Graph API authentication and email fetching
- `app/Http/Controllers/EmailController.php` - Controls the email-related routes and views
- `config/graph.php` - Microsoft Graph configuration
- `resources/views/emails/index.blade.php` - Main email display page
- `routes/web.php` - Application routes

### Routes

- `/` - Welcome page with link to email reader
- `/emails` - Main email reader page
- `/auth/microsoft` - Redirects to Microsoft authentication
- `/auth/microsoft/callback` - Handles OAuth callback
- `/auth/logout` - Logs out from Microsoft Graph
- `/api/emails/refresh` - AJAX endpoint for refreshing emails

## Security Features

- CSRF protection for OAuth state parameter
- Secure token storage in session
- Automatic token refresh when expired
- Input validation and error handling

## Troubleshooting

### Common Issues

1. **"Invalid redirect URI"** - Make sure the redirect URI in Azure matches exactly what's in your `.env` file
2. **"Insufficient privileges"** - Ensure the required permissions are granted and admin consent is provided
3. **"Token expired"** - The application should automatically refresh tokens, but you may need to re-authenticate

### Debug Mode

You can enable debug mode by setting `APP_DEBUG=true` in your `.env` file to see detailed error messages.

## Production Considerations

For production deployment, consider:

1. Use HTTPS for all URLs
2. Store secrets in secure environment variables
3. Implement proper error logging
4. Add rate limiting for API calls
5. Consider using database storage for tokens instead of sessions
6. Implement proper user management and multi-tenancy

## License

This is a proof of concept application. Use it as a reference for your own implementation.
