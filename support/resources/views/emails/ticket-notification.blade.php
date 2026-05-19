<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Notification — YG Support</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #1a1a1a; background: #f5f5f5; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background: #ff003c; padding: 32px 40px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 20px; font-weight: 700; margin: 0; letter-spacing: -0.5px;">
                                YG Support
                            </h1>
                            <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin: 8px 0 0 0;">
                                Ticket Notification
                            </p>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 32px 40px;">
                            <h2 style="font-size: 18px; font-weight: 600; margin: 0 0 4px 0; color: #1a1a1a;">
                                Hi {{ $recipientName }},
                            </h2>
                            <p style="color: #555; font-size: 14px; margin: 0 0 20px 0;">
                                @switch($notificationType)
                                    @case('created')
                                        Your ticket has been created. Here's a summary:
                                        @break
                                    @case('user_replied')
                                        You added a new reply to your ticket:
                                        @break
                                    @case('agent_replied')
                                        Our support team has responded to your ticket:
                                        @break
                                    @default
                                        There's an update on your ticket:
                                @endswitch
                            </p>

                            {{-- Ticket Card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #fafafa; border-radius: 8px; border: 1px solid #eee; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 20px; border-bottom: 1px solid #eee;">
                                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 4px;">
                                            Ticket #{{ $ticket->id }} · {{ ucfirst($ticket->status) }}
                                        </div>
                                        <div style="font-size: 16px; font-weight: 600; color: #1a1a1a;">
                                            {{ $ticket->subject }}
                                        </div>
                                        <div style="font-size: 12px; color: #888; margin-top: 4px;">
                                            {{ ucfirst($ticket->category) }} · {{ ucfirst($ticket->priority) }} priority
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 20px;">
                                        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 8px;">
                                            Message
                                        </div>
                                        <div style="font-size: 14px; color: #333; white-space: pre-wrap;">
                                            {{ $messageBody }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.url') }}/tickets/{{ $ticket->id }}"
                                           style="display: inline-block; background: #ff003c; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 14px; font-weight: 600;">
                                            View Ticket →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 24px 40px; border-top: 1px solid #eee; text-align: center;">
                            <p style="font-size: 12px; color: #999; margin: 0;">
                                YG Support &middot;
                                <a href="{{ config('app.url') }}" style="color: #ff003c; text-decoration: none;">support.ygxone.com</a>
                            </p>
                            <p style="font-size: 11px; color: #bbb; margin: 8px 0 0 0;">
                                Need help? Visit our <a href="{{ route('knowledge.index') }}" style="color: #ff003c; text-decoration: none;">Knowledge Base</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
