<?php

namespace ApexLegends\Http;

use GuzzleHttp\Psr7\Response;

class ResponseParser
{
    /**
     * Parses a GuzzleHttp Response.
     *
     * Will return one of three types of values:
     * 1. JSON - will attempt to parse the response as JSON first.
     * 2. XML - will also attempt to parse the response as XML if it's not JSON.
     * 3. String - if JSON/XML was invalid and there is a response body, it will just return it as a string.
     * 4. Response - if the JSON/XML parsing failed and the response body was empty, the entire Response object will be returned.
     * 
     * @param Response $response
     * @return mixed object|string|Response
     */
    public static function parse(Response $response) 
    {
        $contents = $response->getBody()->getContents();
        
        // Try to parse response as JSON first.
        $data = json_decode($contents);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            return $data;
        }

        // Try XML because EA lives in the year 2002 apparently.
        // Ultimately, this is probably pretty slow if the response is actually just plain text.
        // I'm not too sure if there's a way to validate XML that would make this faster.
        // Maybe check if the string begins with '<?xml'? Hacky but it would be a bit more efficent.
        libxml_use_internal_errors(true);
        $data = simplexml_load_string($contents);

        if ($data !== false)
        {
            return $data;
        }

        // See if the response body is empty and if not just return it as a string.
        if (!empty($contents))
        {
            return $contents;
        }

        // Finally if everything else fails just return the Guzzle response object.
        return $response;
    }
}