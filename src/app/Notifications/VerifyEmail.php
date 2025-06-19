<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmail extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail']; // メール通知を有効にする
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable); // 認証URL生成

        return (new MailMessage)
                    ->subject('メールアドレス認証のお願い') // 件名を設定
                    ->line('以下のボタンをクリックしてメールアドレスを認証してください。')
                    ->action('メール認証', $verificationUrl) // 認証用ボタンを設定
                    ->line('このリンクは一定期間後に無効となります。')
                    ->line('このメールに覚えがない場合は無視してください。');
    }

    /**
     * Generate the verification URL.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        return url(route('verification.verify', [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ], false));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            // 必要に応じて通知内容を配列形式で定義
        ];
    }
}
