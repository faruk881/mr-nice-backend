<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourierPayoutStatusNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $payout, public $status)
    {
        $this->payout = $payout;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->line('The introduction to the notification.')
    //         ->action('Notification Action', url('/'))
    //         ->line('Thank you for using our application!');
    // }

    /**
     *  Data stored in notifications table 
     * */ 
    public function toDatabase($notifiable): array { 
        return [ 
            'type' => 'order_status', 
            'payout_id' => $this->payout->id,
            'amount' => $this->payout->amount, 
            'currency' => $this->payout->currency, 
            'status' => $this->payout->status, 
            'title' => $this->getTitle(),
            'message' => $this->getMessage()
        ]; 
    }
    protected function getTitle()
{
    return match ($this->payout->status) {
        'requested'  => 'Payout Requested',
        'approved'   => 'Payout Approved',
        'processing' => 'Payout Processing',
        'paid'       => 'Payout Completed',
        'failed'     => 'Payout Failed',
        'cancelled'  => 'Payout Cancelled',
        'rejected'   => 'Payout Rejected',
        default      => 'Payout Update',
    };
}

protected function getMessage()
{
    $amount = number_format($this->payout->amount, 2);
    $currency = $this->payout->currency;

    return match ($this->payout->status) {
        'requested'  => "Your payout of {$amount} {$currency} has been requested and is pending review.",
        'approved'   => "Your payout of {$amount} {$currency} has been approved.",
        'processing' => "Your payout of {$amount} {$currency} is currently being processed.",
        'paid'       => "Your payout of {$amount} {$currency} has been successfully paid to your account.",
        'failed'     => "Your payout of {$amount} {$currency} could not be processed. Please contact support.",
        'cancelled'  => "Your payout of {$amount} {$currency} has been cancelled.",
        'rejected'   => "Your payout of {$amount} {$currency} has been rejected.",
        default      => "There is an update regarding your payout of {$amount} {$currency}.",
    };
}
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
