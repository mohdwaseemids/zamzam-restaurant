<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Push notifications to the mobile app through Firebase Cloud Messaging (HTTP v1).
 *
 * Needs a Firebase service-account JSON pasted in Website settings → App & push.
 * Until that is set, every send is skipped silently.
 */
class PushService
{
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    protected const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string}|null
     */
    protected function credentials(): ?array
    {
        $json = json_decode((string) setting('fcm_service_account', ''), true);

        if (! is_array($json) || empty($json['project_id']) || empty($json['client_email']) || empty($json['private_key'])) {
            return null;
        }

        return ['project_id' => $json['project_id'], 'client_email' => $json['client_email'], 'private_key' => $json['private_key']];
    }

    public function orderStatusChanged(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        $messages = [
            'confirmed' => 'Your order is confirmed. We are getting it ready.',
            'preparing' => 'Your food is being prepared in our kitchen.',
            'out_for_delivery' => 'Your order is on the way!',
            'ready' => 'Your order is ready to collect.',
            'completed' => 'Enjoy your meal! Thank you for ordering from Zam Zam.',
            'cancelled' => 'Your order has been cancelled.',
        ];

        if (isset($messages[$order->status])) {
            $this->toUser($order->user, 'Order '.$order->order_number, $messages[$order->status], [
                'type' => 'order_status',
                'order_number' => $order->order_number,
                'status' => $order->status,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $data  extra key/value pairs the app can read
     * @return int number of devices the message was accepted for
     */
    public function toUser(?User $user, string $title, string $body, array $data = []): int
    {
        if (! $user || ! $this->isConfigured()) {
            return 0;
        }

        $sent = 0;

        foreach ($user->deviceTokens as $device) {
            $sent += $this->send($device, $title, $body, $data) ? 1 : 0;
        }

        return $sent;
    }

    /**
     * @param  array<string, string>  $data
     */
    protected function send(DeviceToken $device, string $title, string $body, array $data): bool
    {
        try {
            $credentials = $this->credentials();

            $response = Http::withToken($this->accessToken($credentials))
                ->timeout(10)
                ->post('https://fcm.googleapis.com/v1/projects/'.$credentials['project_id'].'/messages:send', [
                    'message' => [
                        'token' => $device->token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => array_map('strval', $data),
                    ],
                ]);

            // Firebase says this device no longer exists: stop sending to it.
            if (in_array($response->status(), [404, 410], true) || $response->json('error.details.0.errorCode') === 'UNREGISTERED') {
                $device->delete();

                return false;
            }

            if ($response->failed()) {
                Log::warning('Push notification rejected', ['status' => $response->status(), 'error' => $response->json('error.message')]);

                return false;
            }

            $device->update(['last_used_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            // A push problem must never break an order update.
            Log::warning('Push notification failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Exchange a signed JWT for a short-lived Google access token (cached for 50 minutes).
     *
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    protected function accessToken(array $credentials): string
    {
        return Cache::remember('fcm_access_token_'.md5($credentials['client_email']), now()->addMinutes(50), function () use ($credentials): string {
            $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
            $now = time();

            $unsigned = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('The Firebase private key could not be used to sign the request.');
            }

            $response = Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$encode($signature),
            ]);

            if ($response->failed() || blank($response->json('access_token'))) {
                throw new RuntimeException('Google did not issue an access token: '.($response->json('error_description') ?? $response->status()));
            }

            return $response->json('access_token');
        });
    }
}
