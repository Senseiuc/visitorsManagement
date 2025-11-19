<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TermiiSmsChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTermii')) {
            return;
        }

        $message = $notification->toTermii($notifiable);

        if (empty($message['to']) || empty($message['message'])) {
            return;
        }

        $this->sendSms(
            $message['to'],
            $message['message']
        );
    }

    /**
     * Send SMS via Termii API.
     */
    protected function sendSms(string $to, string $message): void
    {
        $apiKey = config('services.termii.api_key');
        $senderId = config('services.termii.sender_id');
        $apiUrl = config('services.termii.api_url');

        if (empty($apiKey)) {
            Log::warning('Termii API key not configured. SMS not sent.');
            return;
        }

        try {
            $response = Http::post("{$apiUrl}/sms/send", [
                'to' => $this->formatPhoneNumber($to),
                'from' => $senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => 'generic',
                'api_key' => $apiKey,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully via Termii', [
                    'to' => $to,
                    'response' => $response->json(),
                ]);
            } else {
                Log::error('Failed to send SMS via Termii', [
                    'to' => $to,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending SMS via Termii', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format phone number for Termii (should be in international format).
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove spaces, dashes, and parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // If it starts with 0, replace with +234 (Nigeria)
        if (str_starts_with($phone, '0')) {
            $phone = '+234' . substr($phone, 1);
        }

        // If it doesn't start with +, add +234
        if (!str_starts_with($phone, '+')) {
            $phone = '+234' . $phone;
        }

        return $phone;
    }
}
