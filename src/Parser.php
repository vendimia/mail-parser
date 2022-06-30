<?php

namespace Vendimia\MailParser;

class Parser
{
    public function parse($source)//: Message
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

        return new Message($header, $body);
    }
};