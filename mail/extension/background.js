const YG_MAIL_API = 'http://localhost:5007/api/mail/inbox';

// Check for new emails every minute
chrome.alarms.create('checkMail', { periodInMinutes: 1 });

chrome.alarms.onAlarm.addListener((alarm) => {
  if (alarm.name === 'checkMail') {
    fetchNewMail();
  }
});

async function fetchNewMail() {
  try {
    const response = await fetch(YG_MAIL_API);
    if (!response.ok) return;
    
    const emails = await response.json();
    
    // In a real app, we'd compare with locally stored IDs to find *new* ones.
    // For this prototype, if there are unread emails, we show a badge.
    const unreadCount = emails.filter(e => !e.read).length;
    
    if (unreadCount > 0) {
      chrome.action.setBadgeText({ text: unreadCount.toString() });
      chrome.action.setBadgeBackgroundColor({ color: '#ea4335' });
      
      // Get the latest unread to show a notification
      const latestUnread = emails.find(e => !e.read);
      if (latestUnread) {
         chrome.notifications.create({
            type: 'basic',
            iconUrl: 'icon.png',
            title: `New Mail from ${latestUnread.from}`,
            message: latestUnread.subject,
            priority: 2
         });
      }
    } else {
      chrome.action.setBadgeText({ text: '' });
    }
  } catch (error) {
    console.error('YG Mail Sync Error:', error);
  }
}

// Initial check when extension loads
fetchNewMail();
