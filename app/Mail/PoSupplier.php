<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PoSupplier extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     */
    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.po-supplier')
                    ->attach($this->data['file_path'])
                    ->with([
                        'po_number' => $this->data['po_number'],
                        'month' => $this->data['month'],
                        'year' => $this->data['year'],
                        'date' => $this->data['date'],
                        'supplier' => $this->data['supplier'],
                    ]);
    }
}
