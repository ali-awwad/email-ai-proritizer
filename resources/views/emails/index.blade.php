<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Microsoft Graph - Unread Emails</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .email-card {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        .email-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .user-info {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .no-emails {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .loading {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-4 text-center mb-0">
                    <i class="fas fa-envelope-open-text text-primary me-3"></i>
                    Microsoft Graph Email Reader
                </h1>
                <p class="text-center text-muted mt-2">Proof of Concept - Last 5 Unread Emails</p>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(isset($userInfo))
            <!-- User Info -->
            <div class="user-info">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-1">
                            <i class="fas fa-user-circle me-2"></i>
                            Welcome, {{ $userInfo['display_name'] }}!
                        </h3>
                        <p class="mb-0 opacity-75">
                            <i class="fas fa-envelope me-2"></i>
                            {{ $userInfo['email'] }}
                        </p>
                        @if(isset($userInfo['user_type']) && $userInfo['user_type'] !== 'Member')
                            <div class="mt-2">
                                <small class="badge bg-warning text-dark">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    External/Guest Account Detected
                                </small>
                            </div>
                        @endif
                        @if(isset($userInfo['account_enabled']) && !$userInfo['account_enabled'])
                            <div class="mt-1">
                                <small class="badge bg-danger">
                                    <i class="fas fa-times me-1"></i>
                                    Account not fully enabled
                                </small>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 text-md-end">
                        <form method="POST" action="{{ route('auth.logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-light">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            @if(count($emails) == 0)
                <!-- Account Type Help -->
                <div class="alert alert-info" role="alert">
                    <h5 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        Unable to Access Mailbox
                    </h5>
                    <p class="mb-2">The current account doesn't have access to an Exchange Online mailbox. This can happen when:</p>
                    <ul class="mb-2">
                        <li>You're using a guest/external account in an Azure AD tenant</li>
                        <li>The account doesn't have an active Exchange Online license</li>
                        <li>The mailbox is hosted on-premises (not in the cloud)</li>
                    </ul>
                    <hr>
                    <p class="mb-0">
                        <strong>Try signing in with:</strong> A personal Microsoft account (@outlook.com, @hotmail.com, @live.com) 
                        or an organizational account with Exchange Online access.
                    </p>
                </div>
            @endif

            <!-- Emails Section -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3 mb-0">
                            <i class="fas fa-inbox me-2 text-primary"></i>
                            Unread Emails
                            <span class="badge bg-primary ms-2" id="email-count">{{ count($emails) }}</span>
                        </h2>
                        <button class="btn btn-outline-primary" onclick="refreshEmails()">
                            <i class="fas fa-sync-alt me-2" id="refresh-icon"></i>
                            Refresh
                        </button>
                    </div>

                    <div id="emails-container">
                        @if(count($emails) > 0)
                            @foreach($emails as $email)
                                <div class="card email-card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5 class="card-title mb-2">
                                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                                    {{ $email['subject'] }}
                                                </h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-user me-2"></i>
                                                    <strong>From:</strong> {{ $email['from'] }}
                                                    @if($email['from_email'])
                                                        <small class="text-muted">({{ $email['from_email'] }})</small>
                                                    @endif
                                                </p>
                                                <p class="card-text">{{ $email['body_preview'] }}</p>
                                            </div>
                                            <div class="col-md-3 text-md-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ \Carbon\Carbon::parse($email['received_date'])->format('M d, Y H:i') }}
                                                </small>
                                                <br>
                                                <span class="badge bg-warning text-dark mt-2">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    Unread
                                                </span>
                                                @if(isset($email['inference_classification']) && $email['inference_classification'] === 'focused')
                                                    <span class="badge bg-info text-white mt-2 ms-1">
                                                        <i class="fas fa-star me-1"></i>
                                                        Focused
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="no-emails">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No Unread Emails</h4>
                                <p class="text-muted">You're all caught up! No unread emails found.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Loading indicator -->
                    <div class="loading text-center py-4" id="loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Refreshing emails...</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Not authenticated -->
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="card">
                        <div class="card-body py-5">
                            <i class="fas fa-lock fa-4x text-primary mb-4"></i>
                            <h3 class="card-title">Authentication Required</h3>
                            <p class="card-text text-muted mb-4">
                                Please sign in with your Microsoft account to access your emails.
                            </p>
                            <a href="{{ route('auth.microsoft') }}" class="btn btn-primary btn-lg">
                                <i class="fab fa-microsoft me-2"></i>
                                Sign in with Microsoft
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Floating refresh button -->
    @if(isset($userInfo))
        <button class="btn btn-primary refresh-btn" onclick="refreshEmails()" title="Refresh Emails">
            <i class="fas fa-sync-alt"></i>
        </button>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function refreshEmails() {
            const refreshIcon = document.getElementById('refresh-icon');
            const loading = document.getElementById('loading');
            const container = document.getElementById('emails-container');
            const emailCount = document.getElementById('email-count');
            
            // Show loading state
            refreshIcon.classList.add('fa-spin');
            loading.style.display = 'block';
            container.style.opacity = '0.5';
            
            try {
                const response = await fetch('{{ route("emails.refresh") }}', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update email count
                    if (emailCount) {
                        emailCount.textContent = data.count;
                    }
                    
                    // Update emails container
                    if (data.emails.length > 0) {
                        let emailsHtml = '';
                        data.emails.forEach(email => {
                            const receivedDate = new Date(email.received_date).toLocaleDateString('en-US', {
                                year: 'numeric', month: 'short', day: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            });
                            
                            emailsHtml += `
                                <div class="card email-card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5 class="card-title mb-2">
                                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                                    ${email.subject}
                                                </h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-user me-2"></i>
                                                    <strong>From:</strong> ${email.from}
                                                    ${email.from_email ? `<small class="text-muted">(${email.from_email})</small>` : ''}
                                                </p>
                                                <p class="card-text">${email.body_preview}</p>
                                            </div>
                                            <div class="col-md-3 text-md-end">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    ${receivedDate}
                                                </small>
                                                <br>
                                                <span class="badge bg-warning text-dark mt-2">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    Unread
                                                </span>
                                                ${email.inference_classification === 'focused' ? 
                                                    '<span class="badge bg-info text-white mt-2 ms-1"><i class="fas fa-star me-1"></i>Focused</span>' : 
                                                    ''
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        container.innerHTML = emailsHtml;
                    } else {
                        container.innerHTML = `
                            <div class="no-emails">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">No Unread Emails</h4>
                                <p class="text-muted">You're all caught up! No unread emails found.</p>
                            </div>
                        `;
                    }
                    
                    // Show success message
                    showAlert('success', 'Emails refreshed successfully!');
                } else {
                    showAlert('danger', 'Failed to refresh emails: ' + data.error);
                }
            } catch (error) {
                showAlert('danger', 'Error refreshing emails: ' + error.message);
            } finally {
                // Hide loading state
                refreshIcon.classList.remove('fa-spin');
                loading.style.display = 'none';
                container.style.opacity = '1';
            }
        }
        
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            const container = document.querySelector('.container');
            const firstRow = container.querySelector('.row');
            firstRow.insertAdjacentHTML('afterend', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = container.querySelector('.alert:last-of-type');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
