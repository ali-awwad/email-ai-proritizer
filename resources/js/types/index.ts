export interface Email {
  id: string;
  subject: string;
  from: string;
  preview: string;
  receivedDateTime: string;
  ai_analysis: {
    summary: string;
    priority: 'high' | 'medium' | 'low';
    category: string;
    requires_response: boolean;
    sentiment: 'positive' | 'negative' | 'neutral';
    action_items: string[];
    sender_authority: 'high' | 'medium' | 'low';
    sender_title: string;
  };
}

export interface UserInfo {
  displayName: string;
  mail: string;
  id: string;
}

export interface DailySummary {
  total_emails: number;
  high_priority: number;
  medium_priority: number;
  low_priority: number;
  summary_text: string;
  prioritized_items: string[];
}

export interface EmailsPageProps {
  emails: Email[];
  userInfo: UserInfo | null;
  dailySummary: DailySummary;
  error?: string;
}