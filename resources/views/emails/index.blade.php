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
                    
                    <!-- Daily Summary Dropdown -->
                    @if(isset($dailySummary) && $dailySummary['total_emails'] > 0)
                        <div class="row justify-content-center mb-4">
                            <div class="col-md-10">
                                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#dailySummaryCollapse" style="cursor: pointer;">
                                            <div class="text-white">
                                                <h5 class="mb-1">
                                                    <i class="fas fa-brain me-2"></i>
                                                    Quick Summary
                                                </h5>
                                                <p class="mb-0 opacity-75">{{ $dailySummary['summary_text'] }}</p>
                                            </div>
                                            <i class="fas fa-chevron-down text-white" id="summary-arrow"></i>
                                        </div>
                                        
                                        <div class="collapse mt-3" id="dailySummaryCollapse">
                                            <hr class="border-white opacity-25">
                                            
                                            <!-- Prioritized Email List -->
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6 class="text-white mb-3">
                                                        <i class="fas fa-sort-amount-down me-2"></i>
                                                        Emails by Priority
                                                    </h6>
                                                    
                                                    @foreach($dailySummary['prioritized_items'] as $item)
                                                        <div class="mb-2 p-2 rounded" style="background: rgba(255,255,255,0.1);">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex align-items-center mb-1">
                                                                        <span class="badge badge-sm me-2" style="background: rgba(255,255,255,0.2);">
                                                                            #{{ $item['index'] }}
                                                                        </span>
                                                                        
                                                                        @if($item['is_vip'])
                                                                            <span class="badge bg-warning text-dark me-2">
                                                                                <i class="fas fa-crown me-1"></i>VIP
                                                                            </span>
                                                                        @endif
                                                                        
                                                                        @php
                                                                            $priorityColors = [
                                                                                'high' => 'danger',
                                                                                'medium' => 'warning', 
                                                                                'low' => 'success'
                                                                            ];
                                                                        @endphp
                                                                        <span class="badge bg-{{ $priorityColors[$item['priority']] }} me-2">
                                                                            {{ ucfirst($item['priority']) }}
                                                                        </span>
                                                                        
                                                                        @if($item['requires_response'])
                                                                            <span class="badge bg-info">
                                                                                <i class="fas fa-reply me-1"></i>Reply
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    <div class="text-white">
                                                                        <strong>{{ Str::limit($item['subject'], 40) }}</strong>
                                                                        <br>
                                                                        <small class="opacity-75">
                                                                            From: {{ $item['from'] }}
                                                                            @if($item['sender_title'])
                                                                                ({{ $item['sender_title'] }})
                                                                            @endif
                                                                        </small>
                                                                        <br>
                                                                        <small class="opacity-75">{{ Str::limit($item['summary'], 80) }}</small>
                                                                    </div>
                                                                    
                                                                    @if(!empty($item['action_items']))
                                                                        <div class="mt-2">
                                                                            <small class="text-white opacity-75">
                                                                                <i class="fas fa-tasks me-1"></i>
                                                                                Actions: {{ implode(', ', array_slice($item['action_items'], 0, 2)) }}
                                                                                @if(count($item['action_items']) > 2)
                                                                                    ...
                                                                                @endif
                                                                            </small>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                
                                                <!-- Summary Stats -->
                                                <div class="col-md-4">
                                                    <h6 class="text-white mb-3">
                                                        <i class="fas fa-chart-pie me-2"></i>
                                                        Quick Stats
                                                    </h6>
                                                    
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between text-white mb-1">
                                                            <small>High Priority</small>
                                                            <small>{{ $dailySummary['priority_breakdown']['high'] }}</small>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-white mb-1">
                                                            <small>Need Response</small>
                                                            <small>{{ $dailySummary['action_required_count'] }}</small>
                                                        </div>
                                                        <div class="d-flex justify-content-between text-white mb-1">
                                                            <small>VIP Senders</small>
                                                            <small>{{ count($dailySummary['high_authority_senders']) }}</small>
                                                        </div>
                                                    </div>
                                                    
                                                    @if(!empty($dailySummary['high_authority_senders']))
                                                        <div>
                                                            <h6 class="text-white mb-2">
                                                                <i class="fas fa-star me-1"></i>
                                                                VIP Alerts
                                                            </h6>
                                                            @foreach(array_slice($dailySummary['high_authority_senders'], 0, 3) as $vip)
                                                                <div class="mb-2 p-2 rounded" style="background: rgba(255,255,255,0.15);">
                                                                    <div class="text-white">
                                                                        <strong class="d-block">{{ $vip['name'] }}</strong>
                                                                        @if($vip['title'])
                                                                            <small class="opacity-75">{{ $vip['title'] }}</small><br>
                                                                        @endif
                                                                        <small class="opacity-75">{{ Str::limit($vip['subject'], 30) }}</small>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

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
                                @php
                                    $isVip = isset($email['ai_analysis']['sender_authority']) && 
                                             $email['ai_analysis']['sender_authority'] === 'high' &&
                                             ($email['ai_analysis']['requires_response'] ?? false);
                                @endphp
                                
                                <div class="card h-100 shadow-sm border-0 {{ $isVip ? 'border-warning' : '' }}" 
                                     style="{{ $isVip ? 'box-shadow: 0 0 15px rgba(255, 193, 7, 0.3) !important;' : '' }}">
                                    
                                    @if($isVip)
                                        <div class="card-header bg-warning text-dark py-2">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-crown me-2"></i>
                                                <strong>VIP - High Authority Sender Needs Response</strong>
                                                @if(isset($email['ai_analysis']['sender_title']) && $email['ai_analysis']['sender_title'])
                                                    <span class="ms-2 badge bg-dark">{{ $email['ai_analysis']['sender_title'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    
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
                                                        @if(isset($email['ai_analysis']['sender_authority']) && $email['ai_analysis']['sender_authority'] === 'high')
                                                            <span class="badge bg-warning text-dark ms-2">
                                                                <i class="fas fa-star me-1"></i>High Authority
                                                            </span>
                                                        @endif
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
    // Handle dropdown arrow rotation
    document.addEventListener('DOMContentLoaded', function() {
        const summaryCollapse = document.getElementById('dailySummaryCollapse');
        const summaryArrow = document.getElementById('summary-arrow');
        
        if (summaryCollapse && summaryArrow) {
            summaryCollapse.addEventListener('shown.bs.collapse', function() {
                summaryArrow.style.transform = 'rotate(180deg)';
            });
            
            summaryCollapse.addEventListener('hidden.bs.collapse', function() {
                summaryArrow.style.transform = 'rotate(0deg)';
            });
        }
    });

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
    
    /* VIP Email styling */
    .border-warning {
        border: 2px solid #ffc107 !important;
    }
    
    /* Smooth arrow rotation */
    #summary-arrow {
        transition: transform 0.3s ease;
    }
    
    /* Summary dropdown styling */
    .collapse {
        transition: all 0.3s ease;
    }
</style>
</body>
</html>
