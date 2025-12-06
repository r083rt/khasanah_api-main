<?php

namespace App\Services\Firebase;

use Illuminate\Support\Facades\Http;

class FirebaseService
{
    /**
     * Send
     */
    public static function send($title, $content, $token, $datas)
    {
        $messages = self::defaultMessage();
        $messages['notification'] = array_merge($messages['notification'], [
            'title' => $title,
            'body' => $content,
        ]);
        $messages['to'] = $token;
        $messages['data'] = $datas;

        $response = Http::withHeaders([
            'Authorization' => config('firebase.key'),
        ])
        ->contentType('application/json')
        ->withBody(json_encode($messages), 'application/json')
        ->post(config('firebase.url'));

        return $response->successful();
    }

    /**
     * Default Message
     */
    public static function defaultMessage()
    {
        return [
            'collapse_key' => 'type_a',
            'priority' => 'high',
            'content_available' => true,
            'notification' => [
                'color' => '#FF0000'
            ],
        ];
    }
}
