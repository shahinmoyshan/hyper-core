<?php

namespace Hyper\Helpers;

use Exception;

/**
 * Class Mail
 * 
 * Provides a utility to build and send emails, including support for attachments,
 * HTML content, and various headers like CC, BCC, and Reply-To.
 * 
 * @package Hyper\Helpers
 * @author Shahin Moyshan <shahin.moyshan2@gmail.com>
 */
class Mail
{
    /**
     * @var array $headers
     * Array to store email headers, such as "From", "Reply-To", "CC", and "BCC".
     */
    private array $headers = [];

    /**
     * @var array $attachments
     * Array to store details of attachments, including file paths and names.
     */
    private array $attachments = [];

    /**
     * @var string $to
     * Recipient email address.
     */
    private string $to;

    /**
     * @var string $subject
     * Subject line of the email.
     */
    private string $subject;

    /**
     * @var string $body
     * The main content of the email, which can be plain text or HTML.
     */
    private string $body;

    /**
     * @var bool $isHtml
     * Flag indicating whether the email body should be treated as HTML. Default is true.
     */
    private bool $isHtml = true;

    /**
     * Sets the "From" header for the email.
     * 
     * @param string $email The sender's email address.
     * @param string $name Optional. The sender's name.
     * @return self
     */
    public function from(string $email, string $name = ''): self
    {
        $from = empty($name) ? $email : "$name <$email>";
        $this->headers[] = "From: $from";
        return $this;
    }

    /**
     * Sets the recipient address for the email.
     * 
     * @param string $email The recipient's email address.
     * @param string $name Optional. The recipient's name.
     * @return self
     */
    public function address(string $email, string $name = ''): self
    {
        $to = empty($name) ? $email : "$name <$email>";
        $this->to = $to;
        return $this;
    }

    /**
     * Sets the "Reply-To" header for the email.
     * 
     * @param string $email The reply-to email address.
     * @param string $name Optional. The reply-to name.
     * @return self
     */
    public function replyTo(string $email, string $name = ''): self
    {
        $replyTo = empty($name) ? $email : "$name <$email>";
        $this->headers[] = "Reply-To: $replyTo";
        return $this;
    }

    /**
     * Adds a CC (carbon copy) recipient.
     * 
     * @param string $email The CC email address.
     * @param string $name Optional. The CC recipient's name.
     * @return self
     */
    public function cc(string $email, string $name = ''): self
    {
        $cc = empty($name) ? $email : "$name <$email>";
        $this->headers[] = "Cc: $cc";
        return $this;
    }

    /**
     * Adds a BCC (blind carbon copy) recipient.
     * 
     * @param string $email The BCC email address.
     * @param string $name Optional. The BCC recipient's name.
     * @return self
     */
    public function bcc(string $email, string $name = ''): self
    {
        $bcc = empty($name) ? $email : "$name <$email>";
        $this->headers[] = "Bcc: $bcc";
        return $this;
    }

    /**
     * Adds an attachment to the email.
     * 
     * @param string $filePath The path to the file to attach.
     * @param string $name Optional. The name to show for the attachment.
     * @return self
     */
    public function attachment(string $filePath, string $name = ''): self
    {
        $this->attachments[] = ['path' => $filePath, 'name' => $name];
        return $this;
    }

    /**
     * Sets the subject of the email.
     * 
     * @param string $subject The subject line.
     * @return self
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets the body content of the email.
     * 
     * @param string $body The body content.
     * @param bool $isHtml Optional. True if the body is HTML; false if plain text. Default is true.
     * @return self
     */
    public function body(string $body, bool $isHtml = true): self
    {
        $this->isHtml = $isHtml;
        $this->body = $body;
        return $this;
    }

    /**
     * Sends the email using PHP's mail() function.
     * 
     * @return bool True if the mail was successfully accepted for delivery, otherwise false.
     */
    public function send(): bool
    {
        return mail($this->to, $this->subject, $this->body, $this->buildHeaders());
    }

    /**
     * Builds and formats the email headers, including MIME and content-type if necessary.
     * 
     * Handles attachments by setting up a multipart email with boundaries.
     * 
     * @return string The formatted headers string.
     * @throws Exception If an attachment cannot be read.
     */
    private function buildHeaders(): string
    {
        if ($this->isHtml) {
            // Set the content type to HTML to allow for HTML email bodies.
            $this->headers[] = "MIME-Version: 1.0";
            $this->headers[] = "Content-type: text/html; charset=UTF-8";
        }

        if (!empty($this->attachments)) {
            $boundary = md5(time());
            $this->headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";

            /**
             * Build the email body with attachments.
             * 
             * We use a boundary to separate the text/html part from the attachments.
             * Each attachment is a separate part with its own Content-Type and Content-Disposition headers.
             * We use base64 encoding to encode the attachments, and chunk_split() to split the encoded data into lines.
             */
            $body = "--$boundary\r\n";
            $body .= "Content-Type: text/" . ($this->isHtml ? "html" : "plain") . "; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($this->body)) . "\r\n";

            foreach ($this->attachments as $attachment) {
                $filePath = $attachment['path'];
                $fileName = empty($attachment['name']) ? basename($filePath) : $attachment['name'];
                $fileData = file_get_contents($filePath);

                if ($fileData === false) {
                    throw new Exception("Failed to read file: $filePath");
                }

                /**
                 * Add an attachment to the email.
                 * 
                 * Set the Content-Type to application/octet-stream, and the Content-Disposition to attachment.
                 * Use the file name as the name in the Content-Disposition header, and the file contents as the attachment data.
                 */
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
                $body .= chunk_split(base64_encode($fileData)) . "\r\n";
            }

            /**
             * Close the multipart/mixed boundary.
             */
            $body .= "--$boundary--";
            $this->body = $body;
        }

        return implode("\r\n", $this->headers); // implode the headers into a single string
    }
}
