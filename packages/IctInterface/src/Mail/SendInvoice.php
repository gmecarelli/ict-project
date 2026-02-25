<?php

namespace Packages\IctInterface\Mail;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoice extends Mailable
{
    use Queueable, SerializesModels;

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
        $fromMail = session()->get('loggedUser')->email.'@ictlabs.it';
        $fromName = session()->get('loggedUser')->name;
        $cc = Str::of($this->data['cc'])->explode(';');
        $this->data['body'] = nl2br($this->data['body']);
        $mail = $this->subject($this->data['subject'])
                ->from($fromMail, $fromName)
                ->to($this->data['to'])
                ->cc($cc)
                ->view('email.invoice', ['data' => $this->data]);
        foreach($this->data['filepath'] as $filepath) {
            $mail->attach($filepath);
        }

        return $mail;
    }
}
