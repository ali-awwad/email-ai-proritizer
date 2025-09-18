<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daily Email Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            @if(isset($userInfo))
                <!-- Header Section -->
                <div class="text-center mb-4">
                    <h1 class="display-4 mb-2">
                        <i class="fas fa-envelope-open-text text-primary me-3"></i>
                        Daily Email Summary
                    </h1>
                    <p class="lead text-muted">
                        Good morning! Here's your AI-powered summary of today's important emails.
                    </p>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center py-3">
                                    <h5 class="text-primary mb-1">{{ count($emails) }}</h5>
                                    <small class="text-muted">Unread Emails</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center py-3">
                                    @php
                                        $highPriorityCount = collect($emails)->filter(function($email) {
                                            return isset($email['ai_analysis']['priority']) && $email['ai_analysis']['priority'] === 'high';
                                        })->count();
                                    @endphp
                                    <h5 class="text-danger mb-1">{{ $highPriorityCount }}</h5>
                                    <small class="text-muted">High Priority</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center py-3">
                                    @php
                                        $responseNeededCount = collect($emails)->filter(function($email) {
                                            return isset($email['ai_analysis']['requires_response']) && $email['ai_analysis']['requires_response'];
                                        })->count();
                                    @endphp
                                    <h5 class="text-warning mb-1">{{ $responseNeededCount }}</h5>
                                    <small class="text-muted">Need Response</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Summary Cards -->
                <div class="row">
                    @if(count($emails) > 0)
                        @foreach($emails as $index => $email)
                            <div class="col-12 mb-4">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-body">
                                        <div class="row">
                                            <!-- Email Content -->
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-3">
                                                    <span class="badge badge-lg me-3 fs-6" style="background: linear-gradient(45deg, #007bff, #0056b3); color: white; padding: 8px 12px;">
                                                        #{{ $index + 1 }}
                                                    </span>
                                                    <h5 class="card-title mb-0 fw-bold">{{ $email['subject'] }}</h5>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <p class="text-muted mb-1">
                                                        <i class="fas fa-user me-2"></i>
                                                        <strong>From:</strong> {{ $email['from'] }}
                                                    </p>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-clock me-2"></i>
                                                        <strong>Received:</strong> {{ \Carbon\Carbon::parse($email['received_date'])->format('M j, Y g:i A') }}
                                                    </p>
                                                </div>

                                                @if(isset($email['ai_analysis']['summary']))
                                                    <div class="alert alert-info border-0" style="background: linear-gradient(90deg, #e3f2fd, #f8f9fa);">
                                                        <div class="d-flex align-items-start">
                                                            <i class="fas fa-robot text-info me-2 mt-1"></i>
                                                            <div>
                                                                <strong class="text-info">AI Summary:</strong>
                                                                <p class="mb-0 mt-1">{{ $email['ai_analysis']['summary'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if(isset($email['ai_analysis']['action_items']) && count($email['ai_analysis']['action_items']) > 0)
                                                    <div class="mt-3">
                                                        <h6 class="text-primary mb-2">
                                                            <i class="fas fa-tasks me-1"></i>
                                                            Action Items:
                                                        </h6>
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($email['ai_analysis']['action_items'] as $item)
                                                                <li class="mb-1">
                                                                    <i class="fas fa-chevron-right text-primary me-2"></i>
                                                                    {{ $item }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Priority & Metadata -->
                                            <div class="col-md-4">
                                                <div class="text-end">
                                                    @if(isset($email['ai_analysis']['priority']))
                                                        @php
                                                            $priority = $email['ai_analysis']['priority'];
                                                            $priorityColors = [
                                                                'high' => 'danger',
                                                                'medium' => 'warning', 
                                                                'low' => 'success'
                                                            ];
                                                            $priorityIcons = [
                                                                'high' => 'exclamation-triangle',
                                                                'medium' => 'exclamation-circle',
                                                                'low' => 'check-circle'
                                                            ];
                                                        @endphp
                                                        <span class="badge bg-{{ $priorityColors[$priority] }} fs-6 mb-2" style="padding: 8px 16px;">
                                                            <i class="fas fa-{{ $priorityIcons[$priority] }} me-1"></i>
                                                            {{ ucfirst($priority) }} Priority
                                                        </span>
                                                    @endif

                                                    <div class="mt-3">
                                                        @if(isset($email['ai_analysis']['category']) && $email['ai_analysis']['category'] !== 'personal')
                                                            <div class="mb-2">
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-tag me-1"></i>
                                                                    {{ ucfirst($email['ai_analysis']['category']) }}
                                                                </span>
                                                            </div>
                                                        @endif

                                                        @if(isset($email['ai_analysis']['requires_response']) && $email['ai_analysis']['requires_response'])
                                                            <div class="mb-2">
                                                                <span class="badge bg-warning text-dark">
                                                                    <i class="fas fa-reply me-1"></i>
                                                                    Response Needed
                                                                </span>
                                                            </div>
                                                        @endif

                                                        @if(isset($email['ai_analysis']['sentiment']))
                                                            @php
                                                                $sentiment = $email['ai_analysis']['sentiment'];
                                                                $sentimentColors = [
                                                                    'positive' => 'success',
                                                                    'neutral' => 'secondary',
                                                                    'negative' => 'danger'
                                                                ];
                                                                $sentimentIcons = [
                                                                    'positive' => 'smile',
                                                                    'neutral' => 'meh',
                                                                    'negative' => 'frown'
                                                                ];
                                                            @endphp
                                                            <div class="mb-2">
                                                                <span class="badge bg-{{ $sentimentColors[$sentiment] }}">
                                                                    <i class="fas fa-{{ $sentimentIcons[$sentiment] }} me-1"></i>
                                                                    {{ ucfirst($sentiment) }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                                    <h4 class="text-muted">No Unread Emails</h4>
                                    <p class="text-muted">You're all caught up! Check back later for new emails.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Refresh Button -->
                <div class="text-center mt-4">
                    <button class="btn btn-primary btn-lg" onclick="refreshEmails()" id="refresh-btn">
                        <i class="fas fa-sync-alt me-2" id="refresh-icon"></i>
                        Refresh Summary
                    </button>
                </div>

            @else
                <!-- Authentication Required -->
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card border-0 shadow">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-lock fa-4x text-primary mb-4"></i>
                                <h3 class="card-title">Welcome to Daily Email Summary</h3>
                                <p class="card-text text-muted mb-4">
                                    Get an AI-powered summary of your most important emails to start your day right.
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    async function refreshEmails() {
        const refreshIcon = document.getElementById('refresh-icon');
        const refreshBtn = document.getElementById('refresh-btn');
        
        // Show loading state
        refreshIcon.classList.add('fa-spin');
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Refreshing...';
        
        try {
            const response = await fetch('{{ route("emails.index") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            });
            
            if (response.ok) {
                // Reload the page to show updated emails
                window.location.reload();
            } else {
                throw new Error('Failed to refresh emails');
            }
            
        } catch (error) {
            console.error('Error refreshing emails:', error);
            alert('Failed to refresh emails. Please try again.');
        } finally {
            refreshIcon.classList.remove('fa-spin');
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Refresh Summary';
        }
    }
</script>

<style>
    .card {
        transition: transform 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
    
    .badge-lg {
        font-size: 0.9rem;
        padding: 6px 12px;
    }
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
</style>
</body>
</html>
