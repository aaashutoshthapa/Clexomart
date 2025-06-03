<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $orderItems;
    public $customerEmail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order, $orderItems, $customerEmail)
    {
        $this->order = $order;
        $this->orderItems = $orderItems;
        $this->customerEmail = $customerEmail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->to($this->customerEmail)
                    ->subject('Order Invoice - ClexoMart Order #' . $this->order->order_id)
                    ->view('emails.order-invoice')
                    ->with([
                        'order' => $this->order,
                        'orderItems' => $this->orderItems,
                    ]);
    }
} 