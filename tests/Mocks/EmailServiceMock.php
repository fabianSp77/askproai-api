<?php

namespace Tests\Mocks;

class EmailServiceMock
{
    private array $sentEmails = [];
    
    public function send($to, $subject, $body, $attachments = [])
    {
        $email = [
            'id' => 'email_' . uniqid(),
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachments,
            'sent_at' => now()
        ];
        
        $this->sentEmails[] = $email;
        
        return [
            'success' => true,
            'message_id' => $email['id']
        ];
    }
    
    public function getSentEmails()
    {
        return $this->sentEmails;
    }
    
    public function clearSentEmails()
    {
        $this->sentEmails = [];
    }
}