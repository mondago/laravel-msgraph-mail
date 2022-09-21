<?php

namespace Mondago\MSGraph\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;

class Transport extends AbstractTransport
{
    /**
     * Graph api configuration
     *
     * @var array
     */
    private array $config;

    public function __construct(array $config)
    {
        parent::__construct(null, null);
        $this->config = $config;
    }

    protected function doSend(SentMessage $message): void
    {
        $token = $this->getToken();
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $url = sprintf(
            'https://graph.microsoft.com/v1.0/users/%s/sendMail',
             $this->config['aad_user_email'] ? urlencode($this->config['aad_user_email']) : $email->getFrom()[0]->getEncodedAddress()
        );
        $response = Http::withHeaders([
            'Authorization' => sprintf('Bearer %s', $token),
        ])->post($url, [
            'message' => $this->getMessage($email),
        ]);
        $response->throw();
    }

    public function getToken()
    {
        $url = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->config['tenant']);
        $response = Http::asForm()->post($url, [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);
        $response->throw();

        return $response['access_token'];
    }

    public function __toString(): string
    {
        return 'graph-api';
    }

    /*
     * https://docs.microsoft.com/en-us/graph/api/resources/message?view=graph-rest-1.0
     */
    private function getMessage(Email $email)
    {
        return array_filter([
            'from' => $this->getRecipient($email->getFrom()[0]),
            'sender' => $this->getRecipient($email->getFrom()[0]),
            'toRecipients' => $this->getRecipientsCollection($email->getTo()),
            'ccRecipients' => $this->getRecipientsCollection($email->getCc()),
            'bccRecipients' => $this->getRecipientsCollection($email->getBcc()),
            'replyTo' => $this->getRecipientsCollection($email->getReplyTo()),
            'subject' => $email->getSubject(),
            'body' => [
                'contentType' => $email->getTextBody() ? 'Text' : 'HTML',
                'content' => $email->getTextBody() ?? $email->getHtmlBody(),
            ],
            'attachments' => $this->getAttachmentsCollection($email->getAttachments()),
        ]);
    }

    private function getRecipientsCollection(array $addresses): array
    {
        return array_map('self::getRecipient', $addresses);
    }

    /*
     * https://docs.microsoft.com/en-us/graph/api/resources/recipient?view=graph-rest-1.0
     */
    private function getRecipient($address): array
    {
        return [
            'emailAddress' => array_filter([
                'address' => $address->getAddress(),
                'name' => $address->getName(),
            ]),
        ];
    }

    private function getAttachmentsCollection($attachments)
    {
        return array_map('self::getAttachment', $attachments);
    }

    /*
     * https://docs.microsoft.com/en-us/graph/api/resources/fileattachment?view=graph-rest-1.0
     */
    private function getAttachment(DataPart $attachment)
    {
        return array_filter([
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'name' => $attachment->getName() ?? $attachment->getFilename(),
            'contentType' => $attachment->getContentType(),
            'contentBytes' => base64_encode($attachment->getBody()),
        ]);
    }
}
