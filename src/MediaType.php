<?php

namespace Vendimia\MailParser;

/**
 * Class for interface media types, used in Content-Type header
 */
class MediaType
{
    public $type;
    public $subtype;
    public $suffix = null;
    public $parameters = [];

    public function __construct($media_type)
    {
        // Siempre debe haber type/subtype
        [$type, $subtype] = explode('/', $media_type, 2);

        // Hay parámetros?
        $parameters = '';
        $semicolon = strpos($subtype, ';');
        if ($semicolon !== false) {
            $parameters = substr($subtype, $semicolon + 1);
            $subtype = substr($subtype, 0, $semicolon);
        }

        // Hay sufijo?
        $plus = strpos($subtype, '+');
        if ($plus !== false) {
            $suffix = substr($subtype, $plus + 1);
            $subtype = substr($subtype, 0, $plus);
        }

        // Si hay parámetros, los convertimos en un array
        if($parameters) {
            foreach (explode(';', $parameters) as $parameter) {
                $parameter = ltrim($parameter);
                $value = null;

                $equal = strpos($parameter, '=');
                if ($equal !== false) {
                    // FIXME: Esto debería parsear realmente quoted-strings
                    $value = trim(substr($parameter, $equal + 1), '\'"');
                    $parameter = substr($parameter, 0, $equal);
                }
                $this->parameters[$parameter] = $value;
            }
        }

        $this->type = $type;
        $this->subtype = $subtype;
        $this->suffix = $suffix ?? null;
    }
}