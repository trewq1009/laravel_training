<?php

namespace App\Models;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $hash;
    protected $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->hash = $data;
        $this->url = env('APP_URL', 'http://127.0.0.1');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('mail.signup', ['data' => $this->hash, 'url' => $this->url]);
    }
}
