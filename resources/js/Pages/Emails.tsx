import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { 
  Mail, 
  User, 
  Clock, 
  RefreshCw, 
  TrendingUp, 
  MessageSquare,
  Heart,
  Frown,
  Meh,
  CheckCircle,
  AlertCircle,
  Circle
} from 'lucide-react';
import { EmailsPageProps, Email } from '@/types';

const getPriorityColor = (priority: string) => {
  switch (priority) {
    case 'high':
      return 'destructive';
    case 'medium':
      return 'warning';
    case 'low':
      return 'secondary';
    default:
      return 'secondary';
  }
};

const getSentimentIcon = (sentiment: string) => {
  switch (sentiment) {
    case 'positive':
      return <Heart className="w-4 h-4 text-green-500" />;
    case 'negative':
      return <Frown className="w-4 h-4 text-red-500" />;
    default:
      return <Meh className="w-4 h-4 text-gray-500" />;
  }
};

const getPriorityIcon = (priority: string) => {
  switch (priority) {
    case 'high':
      return <AlertCircle className="w-4 h-4 text-red-500" />;
    case 'medium':
      return <Circle className="w-4 h-4 text-yellow-500" />;
    case 'low':
      return <CheckCircle className="w-4 h-4 text-green-500" />;
    default:
      return <Circle className="w-4 h-4 text-gray-500" />;
  }
};

const EmailCard: React.FC<{ email: Email; index: number }> = ({ email, index }) => {
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map(word => word.charAt(0))
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <Card className="w-full mb-4 hover:shadow-lg transition-shadow duration-200">
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <Badge 
              variant="default" 
              className="text-white bg-gradient-to-r from-blue-500 to-blue-600 px-3 py-1"
            >
              #{index + 1}
            </Badge>
            <Badge variant={getPriorityColor(email.ai_analysis.priority)}>
              {getPriorityIcon(email.ai_analysis.priority)}
              <span className="ml-1 capitalize">{email.ai_analysis.priority}</span>
            </Badge>
            {email.ai_analysis.sender_title && (
              <Badge variant="outline">{email.ai_analysis.sender_title}</Badge>
            )}
          </div>
          <div className="flex items-center space-x-2">
            {getSentimentIcon(email.ai_analysis.sentiment)}
            {email.ai_analysis.requires_response && (
              <MessageSquare className="w-4 h-4 text-blue-500" />
            )}
          </div>
        </div>
        <CardTitle className="text-lg font-bold text-gray-900 dark:text-gray-100">
          {email.subject}
        </CardTitle>
      </CardHeader>
      
      <CardContent>
        <div className="space-y-4">
          {/* Sender Info */}
          <div className="flex items-center space-x-3">
            <Avatar className="w-8 h-8">
              <AvatarFallback className="bg-gray-100 text-gray-600 text-sm">
                {getInitials(email.from)}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1">
              <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                {email.from}
              </p>
              <div className="flex items-center space-x-2 text-xs text-gray-500">
                <Clock className="w-3 h-3" />
                <span>{formatDate(email.receivedDateTime)}</span>
              </div>
            </div>
          </div>

          {/* AI Summary */}
          <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 className="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">
              AI Summary
            </h4>
            <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
              {email.ai_analysis.summary}
            </p>
          </div>

          {/* Action Items */}
          {email.ai_analysis.action_items && email.ai_analysis.action_items.length > 0 && (
            <div>
              <h4 className="font-semibold text-sm text-gray-700 dark:text-gray-300 mb-2">
                Action Items
              </h4>
              <ul className="space-y-1">
                {email.ai_analysis.action_items.map((item, idx) => (
                  <li key={idx} className="flex items-start space-x-2 text-sm">
                    <CheckCircle className="w-3 h-3 text-green-500 mt-0.5 flex-shrink-0" />
                    <span className="text-gray-600 dark:text-gray-400">{item}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Category and Authority */}
          <div className="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
            <Badge variant="outline" className="text-xs">
              {email.ai_analysis.category}
            </Badge>
            <Badge 
              variant={email.ai_analysis.sender_authority === 'high' ? 'default' : 'secondary'}
              className="text-xs"
            >
              <TrendingUp className="w-3 h-3 mr-1" />
              {email.ai_analysis.sender_authority} authority
            </Badge>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

const EmptyState: React.FC = () => (
  <Card className="w-full text-center py-12">
    <CardContent>
      <Mail className="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <h3 className="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-2">
        No Unread Emails
      </h3>
      <p className="text-gray-500 dark:text-gray-500">
        You're all caught up! Check back later for new emails.
      </p>
    </CardContent>
  </Card>
);

const LoginPrompt: React.FC = () => (
  <Card className="w-full text-center py-12">
    <CardContent>
      <User className="w-16 h-16 text-blue-500 mx-auto mb-4" />
      <h3 className="text-lg font-semibold text-gray-600 dark:text-gray-400 mb-4">
        Sign in to view your emails
      </h3>
      <Link href="/auth/microsoft">
        <Button className="bg-blue-600 hover:bg-blue-700">
          <User className="w-4 h-4 mr-2" />
          Sign in with Microsoft
        </Button>
      </Link>
    </CardContent>
  </Card>
);

export default function Emails({ emails, userInfo, dailySummary, error }: EmailsPageProps) {
  const handleRefresh = () => {
    router.reload();
  };

  return (
    <>
      <Head title="Daily Email Summary" />
      
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="container mx-auto px-4 py-8 max-w-4xl">
          {/* Header */}
          <div className="text-center mb-8">
            <h1 className="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center justify-center">
              <Mail className="w-10 h-10 text-blue-600 mr-3" />
              Daily Email Summary
            </h1>
            <p className="text-lg text-gray-600 dark:text-gray-400">
              Good morning! Here's your AI-powered summary of today's important emails.
            </p>
          </div>

          {error && (
            <Card className="mb-6 border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20">
              <CardContent className="pt-6">
                <p className="text-red-600 dark:text-red-400">{error}</p>
              </CardContent>
            </Card>
          )}

          {userInfo ? (
            <>
              {/* User Welcome */}
              <Card className="mb-6">
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <Avatar>
                        <AvatarFallback className="bg-blue-100 text-blue-600">
                          {userInfo.displayName.charAt(0).toUpperCase()}
                        </AvatarFallback>
                      </Avatar>
                      <div>
                        <h2 className="font-semibold text-gray-900 dark:text-gray-100">
                          Welcome back, {userInfo.displayName}!
                        </h2>
                        <p className="text-sm text-gray-500">{userInfo.mail}</p>
                      </div>
                    </div>
                    <Link href="/auth/logout" method="post">
                      <Button variant="outline" size="sm">
                        Sign Out
                      </Button>
                    </Link>
                  </div>
                </CardHeader>
              </Card>

              {/* Daily Summary Stats */}
              {dailySummary && (
                <Card className="mb-6">
                  <CardHeader>
                    <CardTitle className="text-xl">Daily Summary</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                      <div className="text-center">
                        <div className="text-2xl font-bold text-blue-600">{dailySummary.total_emails}</div>
                        <div className="text-sm text-gray-500">Total Emails</div>
                      </div>
                      <div className="text-center">
                        <div className="text-2xl font-bold text-red-600">{dailySummary.high_priority}</div>
                        <div className="text-sm text-gray-500">High Priority</div>
                      </div>
                      <div className="text-center">
                        <div className="text-2xl font-bold text-yellow-600">{dailySummary.medium_priority}</div>
                        <div className="text-sm text-gray-500">Medium Priority</div>
                      </div>
                      <div className="text-center">
                        <div className="text-2xl font-bold text-green-600">{dailySummary.low_priority}</div>
                        <div className="text-sm text-gray-500">Low Priority</div>
                      </div>
                    </div>
                    
                    {dailySummary.summary_text && (
                      <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <p className="text-sm text-blue-800 dark:text-blue-200">
                          {dailySummary.summary_text}
                        </p>
                      </div>
                    )}
                  </CardContent>
                </Card>
              )}

              {/* Emails List */}
              <div className="space-y-4">
                {emails && emails.length > 0 ? (
                  emails.map((email, index) => (
                    <EmailCard key={email.id} email={email} index={index} />
                  ))
                ) : (
                  <EmptyState />
                )}
              </div>

              {/* Refresh Button */}
              <div className="text-center mt-8">
                <Button 
                  onClick={handleRefresh}
                  size="lg"
                  className="bg-blue-600 hover:bg-blue-700"
                >
                  <RefreshCw className="w-4 h-4 mr-2" />
                  Refresh Summary
                </Button>
              </div>
            </>
          ) : (
            <LoginPrompt />
          )}
        </div>
      </div>
    </>
  );
}