<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Copy this command to cPanel:</h3>
        <div class="relative">
            <pre class="bg-white dark:bg-gray-800 p-3 rounded text-xs overflow-x-auto border border-gray-300 dark:border-gray-600">{{ $cronJob->cpanel_command }}</pre>
            <button 
                onclick="navigator.clipboard.writeText(`{{ addslashes($cronJob->cpanel_command) }}`); this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy', 2000)"
                class="absolute top-2 right-2 px-2 py-1 bg-primary-600 hover:bg-primary-700 text-white text-xs rounded transition-colors"
            >
                Copy
            </button>
        </div>
    </div>
    
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">📋 Setup Instructions:</h4>
        <ol class="list-decimal list-inside space-y-1 text-xs text-blue-800 dark:text-blue-200">
            <li>Login to cPanel</li>
            <li>Navigate to <strong>Advanced → Cron Jobs</strong></li>
            <li>Paste the command above</li>
            <li>Set schedule to match: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">{{ $cronJob->schedule }}</code></li>
            <li>Click <strong>Add New Cron Job</strong></li>
        </ol>
    </div>
    
    @if($cronJob->is_system)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg border border-yellow-200 dark:border-yellow-800">
        <p class="text-xs text-yellow-800 dark:text-yellow-200">
            ⚠️ This is a system job. Make sure it's configured correctly for your application to function properly.
        </p>
    </div>
    @endif
</div>
