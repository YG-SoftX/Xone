<div class="filament-widget">
    @if($this->isConnected())
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Grafana Analytics Dashboard</h3>
                <a 
                    href="{{ $this->getDashboardUrl() }}" 
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    Open in Grafana
                </a>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <iframe 
                    src="{{ $this->getDashboardUrl() }}?orgId=1&refresh=30s&kiosk" 
                    width="100%" 
                    height="600" 
                    frameborder="0"
                    style="border-radius: 8px;"
                ></iframe>
            </div>
            
            <div class="text-sm text-gray-600">
                <p>Last data export: {{ $this->getLastExport() }}</p>
                <p class="mt-1">Dashboard auto-refreshes every 30 seconds. Data is exported every 5 minutes via cron job.</p>
            </div>
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h4 class="font-semibold text-yellow-800">Grafana Not Configured</h4>
                    <p class="text-yellow-700 mt-1">Configure InfluxDB and Grafana to enable advanced analytics dashboards.</p>
                    <p class="text-yellow-700 mt-2 text-sm">See documentation: ENTERPRISE_MONITORING_GUIDE.md</p>
                </div>
            </div>
        </div>
    @endif
</div>
