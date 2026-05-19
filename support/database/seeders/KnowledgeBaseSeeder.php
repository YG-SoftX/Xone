<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Getting Started Articles ──────────────────────────────────────────
        Article::create([
            'title' => 'Welcome to YGXone — Getting Started Guide',
            'slug' => 'welcome-to-ygxone',
            'type' => 'article',
            'category' => 'getting-started',
            'published' => true,
            'sort_order' => 1,
            'content' => "Welcome to YGXone! Your all-in-one productivity ecosystem.\n\nYGXone brings together Mail, Documents (DocX), Spreadsheets (Xcel), and more into a single, seamless experience. This guide will help you get started.\n\n## Creating Your Account\n\n1. Visit account.ygxone.com and click \"Sign Up\"\n2. Enter your name, email, and create a secure password\n3. Verify your email address\n4. You're in! You can now access all YGXone services\n\n## Navigating the Ecosystem\n\n- **Home** (ygxone.com): Your launchpad — search across all apps, view recent files, and see what's new\n- **Mail** (mail.ygxone.com): Professional email with smart folders and search\n- **DocX** (docs.ygxone.com): Create and edit rich documents\n- **Xcel** (xcel.ygxone.com): Build spreadsheets with formulas and formatting\n\n## Need Help?\n\nUse the support portal (support.ygxone.com) to create tickets, browse the knowledge base, and get answers to common questions.",
        ]);

        Article::create([
            'title' => 'Understanding Your YGXone Dashboard',
            'slug' => 'understanding-dashboard',
            'type' => 'article',
            'category' => 'getting-started',
            'published' => true,
            'sort_order' => 2,
            'content' => "Your YGXone dashboard is the central hub for managing your workflow.\n\n## Dashboard Features\n\n- **Quick Search**: Find anything across all apps instantly\n- **Recent Activity**: See your latest emails, documents, and spreadsheets\n- **App Launcher**: Switch between Mail, DocX, Xcel, and more\n- **Account Menu**: Access settings, billing, and support\n\n## Customizing Your Experience\n\nYou can customize your dashboard by:\n- Pinning frequently used apps to your sidebar\n- Setting notification preferences in account settings\n- Choosing your preferred theme (coming soon)",
        ]);

        // ── Account & Billing Articles ────────────────────────────────────────
        Article::create([
            'title' => 'Managing Your Account Settings',
            'slug' => 'managing-account-settings',
            'type' => 'article',
            'category' => 'account',
            'published' => true,
            'sort_order' => 1,
            'content' => "Your account settings control everything from your profile to security preferences.\n\n## Profile Settings\n\nUpdate your name, email, and profile picture from the Account page.\n\n## Security\n\n- Change your password at any time\n- Enable two-factor authentication for enhanced security\n- Review active sessions and log out of devices remotely\n\n## Notifications\n\nControl which emails and alerts you receive:\n- Ticket updates\n- Product announcements\n- Billing reminders",
        ]);

        Article::create([
            'title' => 'Billing and Subscription FAQ',
            'slug' => 'billing-subscription-faq',
            'type' => 'faq',
            'category' => 'account',
            'published' => true,
            'sort_order' => 2,
            'content' => "Q: What plans does YGXone offer?\nA: We offer Free and Pro plans. The Free plan includes basic mail, document creation, and spreadsheets with limited storage. Pro unlocks unlimited storage, advanced features, and priority support.\n\nQ: How do I upgrade my plan?\nA: Go to Account > Billing and choose your desired plan. You'll be prompted to enter payment details.\n\nQ: Can I cancel at any time?\nA: Yes! You can cancel your subscription at any time. Your paid features will remain active until the end of your billing period.\n\nQ: Do you offer refunds?\nA: We offer a 14-day money-back guarantee on all paid plans. Contact support for assistance.",
        ]);

        // ── YG Mail Articles ──────────────────────────────────────────────────
        Article::create([
            'title' => 'Getting Started with YG Mail',
            'slug' => 'getting-started-mail',
            'type' => 'article',
            'category' => 'mail',
            'published' => true,
            'sort_order' => 1,
            'content' => "YG Mail is a powerful email client designed for productivity.\n\n## Key Features\n\n- **Smart Folders**: Automatically categorize emails (Primary, Social, Promotions)\n- **Powerful Search**: Find any email instantly with full-text search\n- **Compose and Reply**: Rich text editor with attachments\n- **Backup to Drive**: One-click backup of important emails\n\n## Composing an Email\n\nClick the \"Compose\" button to start a new message. You can:\n- Add multiple recipients (To, CC, BCC)\n- Format text with bold, italic, and more\n- Attach files up to 25MB\n- Schedule emails to send later",
        ]);

        Article::create([
            'title' => 'How to Search Emails Effectively',
            'slug' => 'search-emails-effectively',
            'type' => 'article',
            'category' => 'mail',
            'published' => true,
            'sort_order' => 2,
            'content' => "YG Mail's powerful search helps you find what you need fast.\n\n## Basic Search\n\nSimply type in the search bar to search across sender, subject, and body.\n\n## Tips for Better Results\n\n- Search by sender: Type a name or email address\n- Search by subject: Include keywords that appeared in the subject line\n- Search by date: Add date ranges to narrow results\n- Use quotes for exact phrase matching: \"meeting agenda\"\n\n## Search Filters\n\nUse the filter dropdown to narrow results by:\n- Date range\n- Folder (Inbox, Sent, Drafts, Trash)\n- Read/Unread status",
        ]);

        Article::create([
            'title' => 'Mail Backup — How to Save Your Emails',
            'slug' => 'mail-backup-guide',
            'type' => 'article',
            'category' => 'mail',
            'published' => true,
            'sort_order' => 3,
            'content' => "Backing up your emails ensures you never lose important correspondence.\n\n## One-Click Backup\n\nClick \"Backup to Drive\" in the mail sidebar to instantly back up all your emails to your YG Drive storage.\n\n## What Gets Backed Up\n\n- All emails from all folders (Inbox, Sent, Drafts)\n- Attachments are preserved\n- Email metadata (sender, date, subject) is retained\n\n## Restore from Backup\n\nBacked up emails can be accessed from your Drive. Contact support if you need help restoring specific messages.",
        ]);

        // ── YG DocX Articles ──────────────────────────────────────────────────
        Article::create([
            'title' => 'Creating and Editing Documents in DocX',
            'slug' => 'creating-documents-docx',
            'type' => 'article',
            'category' => 'docx',
            'published' => true,
            'sort_order' => 1,
            'content' => "DocX is YGXone's rich document editor.\n\n## Creating a New Document\n\nFrom the DocX dashboard, click \"New Document\" to start. You can:\n- Choose from templates (blank, letter, report, invoice)\n- Start from scratch with full formatting options\n- Import existing documents\n\n## Editing Features\n\n- Rich text formatting (bold, italic, headings, lists)\n- Insert images and tables\n- Add links and bookmarks\n- Set margins, page size, and orientation",
        ]);

        // ── YG Xcel Articles ──────────────────────────────────────────────────
        Article::create([
            'title' => 'Getting Started with Spreadsheets in Xcel',
            'slug' => 'getting-started-xcel',
            'type' => 'article',
            'category' => 'xcel',
            'published' => true,
            'sort_order' => 1,
            'content' => "Xcel is YGXone's spreadsheet application for data analysis and organization.\n\n## Creating a Spreadsheet\n\nFrom the Xcel dashboard, click \"New Spreadsheet\" to create a blank workbook.\n\n## Basic Operations\n\n- Enter data into cells by clicking and typing\n- Use formulas starting with = (e.g., =SUM(A1:A10))\n- Format cells with different number formats, colors, and borders\n- Add multiple sheets to organize your data\n\n## Sharing\n\nSpreadsheets can be shared with other YGXone users for collaboration.",
        ]);

        // ── Troubleshooting Articles ──────────────────────────────────────────
        Article::create([
            'title' => 'Common Login Issues and Solutions',
            'slug' => 'common-login-issues',
            'type' => 'article',
            'category' => 'troubleshooting',
            'published' => true,
            'sort_order' => 1,
            'content' => "Having trouble logging in? Try these solutions:\n\n## Can't Remember Your Password\n\nClick \"Forgot Password\" on the login page to reset it via email.\n\n## Account Locked\n\nAfter multiple failed attempts, your account may be temporarily locked. Wait 15 minutes and try again, or contact support.\n\n## SSO Not Working\n\nIf you're redirected to the SSO page but not logged in:\n- Clear your browser cookies and cache\n- Try an incognito/private window\n- Disable browser extensions that may block cookies\n\n## Still Stuck?\n\nCreate a support ticket at support.ygxone.com and we'll help you out.",
        ]);

        Article::create([
            'title' => 'Page Not Loading or White Screen',
            'slug' => 'page-not-loading',
            'type' => 'article',
            'category' => 'troubleshooting',
            'published' => true,
            'sort_order' => 2,
            'content' => "If a YGXone page isn't loading properly, try these steps:\n\n1. Refresh the page (Ctrl+F5 or Cmd+Shift+R for hard refresh)\n2. Clear your browser cache\n3. Check your internet connection\n4. Try a different browser (Chrome, Firefox, Edge, Safari)\n5. Disable ad blockers or privacy extensions\n6. Check status.ygxone.com for service outages\n\nIf the issue persists, please create a support ticket with:\n- The URL you're trying to access\n- Your browser and version\n- Any error messages you see",
        ]);

        Article::create([
            'title' => 'Slow Performance — What to Do',
            'slug' => 'slow-performance-fix',
            'type' => 'faq',
            'category' => 'troubleshooting',
            'published' => true,
            'sort_order' => 3,
            'content' => "Q: Why is YGXone running slowly?\nA: Slow performance can be caused by:\n- Large attachments loading in Mail\n- Many tabs open in your browser\n- Temporary server load\n- Internet connection speed\n\nQ: How can I speed things up?\nA: Try these tips:\n- Close unused browser tabs\n- Clear browser cache\n- Reduce the number of emails displayed per page in Mail settings\n- Use the search feature instead of scrolling through long lists\n\nQ: Is there a maintenance window?\nA: Planned maintenance happens during low-usage hours (2-4 AM EST). You'll be notified in advance.",
        ]);

        // ── Security Articles ─────────────────────────────────────────────────
        Article::create([
            'title' => 'Keeping Your Account Secure',
            'slug' => 'keeping-account-secure',
            'type' => 'article',
            'category' => 'security',
            'published' => true,
            'sort_order' => 1,
            'content' => "Your security is our priority. Follow these best practices:\n\n## Strong Passwords\n\n- Use a unique password for your YGXone account\n- Combine uppercase, lowercase, numbers, and symbols\n- Avoid using personal information (birthdays, names)\n\n## Two-Factor Authentication\n\nEnable 2FA in your account settings for an extra layer of security.\n\n## Recognize Phishing\n\nYGXone will never ask for your password via email. Report suspicious emails to security@ygxone.com.\n\n## Session Management\n\nReview and manage active sessions from your account settings. Log out of devices you no longer use.",
        ]);

        Article::create([
            'title' => 'Privacy and Data Protection',
            'slug' => 'privacy-data-protection',
            'type' => 'faq',
            'category' => 'security',
            'published' => true,
            'sort_order' => 2,
            'content' => "Q: How does YGXone protect my data?\nA: We use industry-standard encryption for data in transit (TLS 1.3) and at rest (AES-256). Our servers are hosted in secure data centers with 24/7 monitoring.\n\nQ: Do you share my data with third parties?\nA: We never sell your personal data. We only share data when necessary to provide our services (e.g., email delivery) and only with trusted partners who follow strict privacy agreements.\n\nQ: How long do you keep my data?\nA: Your data is kept as long as your account is active. After account deletion, data is purged within 30 days.\n\nQ: Can I export my data?\nA: Yes! Contact support for data export requests.",
        ]);

        $this->command->info('✅ Knowledge base seeded: ' . Article::count() . ' articles and FAQs created.');
    }
}
