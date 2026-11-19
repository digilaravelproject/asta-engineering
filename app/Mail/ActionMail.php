<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
        public ?string $attachmentPath = null,
        public ?string $attachmentName = null,
    ) {}

    public function build(): self
    {
        $mail = $this->subject($this->subjectLine)->view('emails.action');

        if ($this->attachmentPath) {
            $mail->attachFromStorageDisk('public', $this->attachmentPath, $this->attachmentName);
        }

        return $mail;
    }
}
