<?php

namespace Vendimia\MailParser;

use ArrayAccess;
use Iterator;

class Message implements ArrayAccess, Iterator
{
    /** Last 'traversed' part */
    private $last_traversed_part = false;

    public function __construct(
        public Header $header,
        public Body $body,
    )
    {

    }

    public static function fromString($source)
    {
        // Separamos cabecera de cuerpo
        $neck_exists = preg_match('/(\n\n|\r\r|\r\n\r\n)/', $source, $neck, PREG_OFFSET_CAPTURE);

        // Si no hay cuello, asumimos que todo es header, no body
        if (!$neck_exists) {
            $source_header = $source;
            $source_body = '';
        } else {
            $source_header = substr($source, 0, $neck[0][1]);
            $source_body = substr($source, $neck[0][1] + strlen($neck[0][0]));
        }

        $header = new Header($source_header);
        $body = new Body($source_body, $header);

        return new self($header, $body);
    }

    /**
     * Returns the body parts count
     */
    public function partsCount(): int
    {
        return count($this->body->parts) + 1;
    }

    public function offsetExists(mixed $offset): bool
    {
        return key_exists($offset, $this->body->parts);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->body->parts[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->body->parts[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->body->parts[$offset]);
    }

    public function current(): mixed
    {
        return $this->last_traversed_part;
    }

    public function key(): mixed
    {
        return key($this->body->parts);
    }

    public function next(): void
    {
        next($this->body->parts);
        $this->last_traversed_part = current($this->body->parts);
    }

    public function rewind(): void
    {
        reset($this->body->parts);
        $this->last_traversed_part = current($this->body->parts);
    }

    public function valid(): bool
    {
        return $this->last_traversed_part !== false;
    }
}