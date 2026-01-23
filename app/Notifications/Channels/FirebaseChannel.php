<?php

namespace App\Notifications\Channels;

use App\Services\FirebaseNotificationService;
use Illuminate\Notifications\Notification;

class FirebaseChannel
{
    protected FirebaseNotificationService $firebaseService;

    public function __construct(FirebaseNotificationService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toFirebase')) {
            $notification->toFirebase($notifiable);
        }
    }
}
