<?php

namespace App\Services;

use App\Models\Mail;
use App\Models\MailRule;
use Illuminate\Support\Facades\Log;

class MailRuleProcessor
{
    /**
     * Process a mail against all active rules for the user.
     */
    public function process(Mail $mail): void
    {
        $rules = MailRule::where('user_id', $mail->user_id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($rules as $rule) {
            if ($this->checkTrigger($mail, $rule)) {
                $this->applyAction($mail, $rule);
                
                // If the action was 'delete', stop processing other rules
                if ($rule->action_type === 'delete') {
                    break;
                }
            }
        }
    }

    private function checkTrigger(Mail $mail, MailRule $rule): bool
    {
        $value = strtolower($rule->trigger_value);
        
        switch ($rule->trigger_type) {
            case 'subject':
                return str_contains(strtolower($mail->subject), $value);
            case 'sender':
                return str_contains(strtolower($mail->from), $value);
            case 'body':
                return str_contains(strtolower($mail->body), $value);
            default:
                return false;
        }
    }

    private function applyAction(Mail $mail, MailRule $rule): void
    {
        Log::info("Applying mail rule: {$rule->name} on mail ID: {$mail->id}");

        switch ($rule->action_type) {
            case 'move_to':
                $mail->update(['folder' => $rule->action_value]);
                break;
            case 'mark_read':
                $mail->update(['read' => true]);
                break;
            case 'delete':
                $mail->delete();
                break;
        }
    }
}
