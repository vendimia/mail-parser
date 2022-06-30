<?php

namespace Vendimia\MailParser;

use InvalidArgumentException;

/**
 * Parses the message body
 */
class Body
{
    public string $content = '';
    private ?MediaType $media_type = null;
    public array $parts = [];

    private function parseContent($content)
    {
        $this->content = $content;
    }

    private function parseMultipart($body)
    {
        $boundary = $this->media_type->parameters['boundary'] ?? null;

        if (!$boundary) {
            throw new InvalidArgumentException('Multipart boundary not found');
        }

        $pattern = '[(\n|\r|\r\n)--' . addslashes($boundary) . '((\n|\r|\r\n)|(--)(\n|\r|\r\n)?)]';
        $sections = preg_split($pattern, $body);

        // La primera sección es el contenido principal
        $this->parseContent(array_shift($sections));

        // Cada sección que queda es un message
        foreach ($sections as $section) {
            if (!$section) {
                continue;
            }
            $this->parts[] = Message::fromString($section);
        }
    }

    public function __construct(
        $body,
        private Header $header,
    )
    {
        $this->media_type = $header->getLine('content-type');
        if ($this->media_type?->type == 'multipart') {
            $this->parseMultipart($body);
        } else {
            $this->parseContent($body);
        }
    }
}