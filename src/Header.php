<?php

namespace Vendimia\MailParser;

use ArrayAccess;

/**
 * RFC822 Header parser.
 *
 * Header lines will be [unfolded][1] and can be accessed in a case-insensitive
 * fashion using this object as an array. If there are multiple header lines
 * with the same name, they will be joined using a comma. This is the behavior
 * of the method `getLine()`.
 *
 * To get all the header values as an array, use method `get()`.
 *
 * This class can be used with other header-like elements, like the body in a
 * message/delivery-status part.
 *
 * [1] https://datatracker.ietf.org/doc/html/rfc822#section-3.1.1
 */
class Header implements ArrayAccess
{
    private array $entries;
    private array $index;

    /**
     * Parses header lines into an array and an index array.
     */
    public static function parseHeaderLines($source)
    {
        $idx = 0;
        $entries = [];
        $index = [];

        // Primero desplegamos las caberceras plegadas
        $source = preg_replace('/(\n|\r|\r\n)(\t| )+/', ' ', $source);

        foreach (preg_split('/(\n|\r|\r\n)/', $source) as $line) {

            // Ignoramos líneas vacías
            if (!$line) {
                continue;
            }

            // Copiado de Mail_MimeDecode
            if (substr($line, 0, 5) == 'From ') {
                $line = 'Return-Path: ' . substr($line, 5);
            }

            $colon = strpos($line, ':');
            if ($colon !== false) {
                $name = substr($line, 0, $colon);
                $value = ltrim(substr($line, $colon + 1));
            } else {
                $name = $line;
                $value = '';
            }
            $lower_name = strtolower($name);

            if($lower_name == 'content-type') {
                $value = new MediaType($value);
            }

            $entries[$idx] = compact('name', 'value');
            $index[$lower_name][] = $idx;

            $idx++;
        }

        return [$entries, $index];
    }

    /**
     * Parses the mail header from a string
     */
    public function __construct(string $header_lines)
    {
        [$this->entries, $this->index] = self::parseHeaderLines($header_lines);
    }

    /**
     * Returns the values from all the headers with $name
     */
    public function get($name, $default = null): ?array
    {
        $name = strtolower($name);
        if (!$this->has($name)) {
            return $default;
        }

        $header = [];
        foreach ($this->index[$name] as $index) {
            $header[] = $this->entries[$index]['value'];
        }

        return $header;
    }

    /**
     * Returns whether header $name exists or not
     */
    public function has($name): bool
    {
        return key_exists($name, $this->index);
    }

    /**
     * Returns a single string from all the headers with $name
     *
     * If there is only one header line, then returns it directly. Useful with
     * some special headers treated as object, like Content-Type.
     */
    public function getLine($name, $join = ',', $default = null): ?string
    {
        $lines = $this->get($name, default: $default);

        if(!$lines) {
            return $default;
        }

        if (count($lines) == 1) {
            return $lines[0];
        }

        return join($join, $lines);
    }

    /**
     * Returns an array of all headers with the format [name, value]
     */
    public function getAll(): array
    {
        return $this->entries;
    }

    /**
     * ArrayAccess implementation
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * ArrayAccess implementation
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getLine($offset);
    }

    /**
     * ArrayAccess implementation
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {

    }

    public function offsetUnset(mixed $offset): void
    {

    }
}