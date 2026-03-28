<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $order, public $status)
    {
        $this->order = $order;
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
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number, 
            'status' => $this->status, 
            'customer_id' => $this->order->customer_id ,
            'title' => $this->getTitle(),
            'message' => $this->getMessage()
        ]; 
    }

    protected function getTitle() 
    { 
        return match ($this->status) { 
            'pending_payment' => 'Order Created',
            'pending' => 'Order Pending',
            'accepted' => 'Order Accepted', 
            'pickedup' => 'Order Picked Up', 
            'delivered' => 'Order Delivered', 
            'cancelled' => 'Order Cancelled',
            'delivery_approved' => 'Delivery Approved', 
            default => 'Order Update', 
        }; 
    } 
    
    protected function getMessage() 
    { 
        return match ($this->status) { 
            'pending_payment' => "Your order #{$this->order->order_number } has been created successfully.", 
            'pending' => "Your order #{$this->order->order_number} payment received and waiting for the courier to accept.", 
            'accepted' => "Your order #{$this->order->order_number} your has been accepted by courier", 
            'pickedup' => "Your order #{$this->order->order_number} has been picked up.", 
            'delivered' => "Your order #{$this->order->order_number} has been delivered successfully.", 
            'cancelled' => "Your order #{$this->order->order_number} has been cancelled. You will get your refund shortly",
            'delivery_approved' => "Your delivery request #{$this->order->order_number} has been approved and your wallet balance updated.",
             
            default => "There is an update for your order #{$this->order->order_number}.", 
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
            'type' => 'order_status',
            'order_id' => $this->order->id, 
            'status' => $this->status
        ];
    }
}
