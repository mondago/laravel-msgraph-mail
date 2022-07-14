<?php

namespace Mondago\MSGraph\Mail;

use GuzzleHttp\ClientInterface;
use Illuminate\Mail\Transport\Transport as LaravelTransport;
use Illuminate\Support\Str;

class Transport extends LaravelTransport
{
    /**
     * Graph api configuration
     * @var array
     */
    private $config;

    private $http;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->config = $config;
        $this->http = $client;
    }

    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);
        $token = $this->getToken();
        $emailMessage = $this->getMessage($message);
        $url = sprintf('https://graph.microsoft.com/v1.0/users/%s/sendMail', urlencode($emailMessage['from']['emailAddress']['address']));

        try {
            $this->http->post($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => sprintf('Bearer %s', $token),
                ],
                'json' => [
                    'message' => $emailMessage,
                ],
            ]);

            $this->sendPerformed($message);

            return $this->numberOfRecipients($message);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getToken()
    {
        $url = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->config['tenant']);
        try {
            $response = $this->http->request('POST', $url, [
                'form_params' => [
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials'
                ],
            ]);
            $data = json_decode($response->getBody()->getContents());

            return $data->access_token;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function __toString(): string
    {
        return 'graph-api';
    }

    /*
     * @link https://docs.microsoft.com/en-us/graph/api/resources/message?view=graph-rest-1.0
     */
    private function getMessage(\Swift_Mime_SimpleMessage $email)
    {
        return array_filter([
            "from" => $this->getRecipientsCollection($email->getFrom())[0],
            "sender" => $this->getRecipientsCollection($email->getFrom())[0],
            "toRecipients" => $this->getRecipientsCollection($email->getTo()),
            "ccRecipients" => $this->getRecipientsCollection($email->getCc()),
            "bccRecipients" => $this->getRecipientsCollection($email->getBcc()),
            "replyTo" => $this->getRecipientsCollection($email->getReplyTo()),
            "subject" => $email->getSubject(),
            "body" => [
                "contentType" => $this->getContentType($email),
                "content" => $email->getBody()
            ],
            "attachments" => $this->getAttachmentsCollection($email->getChildren())
        ]);
    }

    private function getRecipientsCollection($addresses): array
    {
        $collection = [];
        if (!$addresses) {
            return [];
        }
        if (is_string($addresses)) {
            $addresses = [
                $addresses => null,
            ];
        }

        foreach ($addresses as $email => $name) {
            # https://docs.microsoft.com/en-us/graph/api/resources/recipient?view=graph-rest-1.0
            $collection[] = [
                'emailAddress' => [
                    'name' => $name,
                    'address' => $email,
                ],
            ];
        }

        return $collection;
    }

    private function getAttachmentsCollection($attachments)
    {
        $collection = [];

        foreach ($attachments as $attachment) {
            if (!$attachment instanceof \Swift_Mime_Attachment) {
                continue;
            }
            // https://docs.microsoft.com/en-us/graph/api/resources/fileattachment?view=graph-rest-1.0
            $collection[] = [
                'name' => $attachment->getFilename(),
                'contentId' => $attachment->getId(),
                'contentType' => $attachment->getContentType(),
                'contentBytes' => base64_encode($attachment->getBody()),
                'size' => strlen($attachment->getBody()),
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'isInline' => $attachment instanceof \Swift_Mime_EmbeddedFile,
            ];

        }

        return $collection;
    }

    public function getContentType(\Swift_Mime_SimpleMessage $email): string
    {
        if (Str::contains($email->getBodyContentType(), ['html'])) {
            return 'HTML';
        } else {
            if (Str::contains($email->getBodyContentType(), ['text', 'plain'])) {
                return 'Text';
            }
        }
        return 'HTML';
    }
}
