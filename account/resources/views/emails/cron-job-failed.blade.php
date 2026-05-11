<x-mail::message>
# 🚨 Cron Job Failure Alert

A cron job has failed in your YG Account system.

## Job Details

**Job Name:** {{ $job->name }}  
**Description:** {{ $job->description }}  
**Schedule:** {{ $job->schedule }}  
**Status:** <span style="color: #e74c3c;">❌ Failed</span>  
**Success Rate:** {{ number_format($job->success_rate, 1) }}%  
**Total Runs:** {{ $job->total_runs }}  
**Failed Runs:** {{ $job->failed_runs }}  

@if($job->last_run_at)
**Last Run:** {{ $job->last_run_at->format('M d, Y H:i:s') }}  
@endif

## Error Message

```
{{ $error }}
```

@if($failures > 1)
⚠️ **This job has failed {{ $failures }} consecutive times!**
@endif

## Recommended Actions

1. Check the job command for syntax errors
2. Verify file permissions and paths
3. Review server resource usage (CPU/RAM)
4. Test the job manually via admin panel
5. Check application logs: `storage/logs/cron.log`

<x-mail::button :url="$adminUrl" color="primary">
View in Admin Panel
</x-mail::button>

---

*This is an automated alert from YG Account Cron Job Monitor.*  
*To disable these notifications, update the job configuration or adjust alert thresholds.*
</x-mail::message>
