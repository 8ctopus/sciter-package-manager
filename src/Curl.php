<?php declare(strict_types=1);

namespace Oct8pus\SPM;

class Curl
{
    /**
     * Download url
     * @param  string $url
     * @param  string& $content
     * @param  string& $info
     * @param  bool $follow follow redirections
     * @return bool true on curl success, false otherwise
     * @note response status codes which indicate errors (such as 404 Not found) are not regarded as failure
     */
    private static function download(string $url, string& $content, array& $info, bool $follow) : bool
    {
        $options = self::get_curl_options($url);

        if ($follow) {
            // follow redirections
            $options[CURLOPT_FOLLOWLOCATION] = true;
            // how many redirections to follow
            $options[CURLOPT_MAXREDIRS]      = 3;
        }

        return self::curl2($options, $content, $info);
    }

    /**
     * Download url 2
     * @param  string $url
     * @param  string& $content
     * @param  string& $info
     * @param  bool $follow follow redirections
     * @return bool true if asset exists, false otherwise
     */
    public static function download2(string $url, string& $content, array& $info, bool $follow) : bool
    {
        return self::download($url, $content, $info, $follow) && $info['http_code'] < ($follow ? 400 : 300);
    }

    /**
     * Test url
     * @param  string $url
     * @param  string& $info
     * @param  bool $follow follow redirections
     * @return bool true on curl success, false otherwise
     * @note response status codes which indicate errors (such as 404 Not found) are not regarded as failure
     */
    private static function test(string $url, array& $info, $follow) : bool
    {
        $options = self::get_curl_options($url);

        // do not return body
        $options[CURLOPT_NOBODY] = true;

        if ($follow) {
            // follow redirections
            $options[CURLOPT_FOLLOWLOCATION] = true;
            // how many redirections to follow
            $options[CURLOPT_MAXREDIRS]      = 3;
        }

        $content = '';

        return self::curl2($options, $content, $info);
    }

    /**
     * Test url 2
     * @param  string $url
     * @param  string& $info
     * @param  bool $follow follow redirections
     * @return bool true if asset exists, false otherwise
     */
    public static function test2(string $url, array& $info, $follow) : bool
    {
        return self::test($url, $info, $follow) && $info['http_code'] < ($follow ? 400 : 300);
    }

    /**
     * Get curl error
     * @param  array& $info
     * @return string
     */
    public static function error(array& $info) : string
    {
        if (isset($info['error']))
            return "curl {$info['error']}";
        else
            return "{$info['http_code']} - ". self::response_code($info['http_code']);
    }

    /**
     * Download file
     * @param  string $url
     * @param  string $file
     * @param  string& $info
     * @param  bool $follow follow redirections
     * @return bool true on curl success, false otherwise
     * @note response status codes which indicate errors (such as 404 Not found) are not regarded as failure
     */
    public static function downloadFile(string $url, string $file, array& $info, bool $follow) : bool
    {
        $buffer = '';
        $info   = [];

        if (!self::download($url, $buffer, $info, $follow))
            return false;

        return file_put_contents($file, $buffer) !== false;
    }

    /**
     * Curl request
     * @param  array  $options
     * @param  string& $content
     * @param  array& $info
     * @return bool true on curl success, false otherwise
     */
    private static function curl2(array $options, string& $content, array& $info) : bool
    {
        // reset return variables
        $content = '';
        $info    = [];

        // initiate curl
        $session = curl_init();

        // set options
        curl_setopt_array($session, $options);

        // run curl
        $content = curl_exec($session);

        // get curl request info
        $info = curl_getinfo($session);

        // check for curl error
        if ($content === false)
            $info['error'] = curl_error($session);

        // convert numeric values
        $info['http_code']               = (int) $info['http_code'];
        $info['download_content_length'] = (int) $info['download_content_length'];

        curl_close($session);

        return $content !== false;
    }

    /**
     * Get curl options
     * @param  string $url
     * @return array
     */
    private static function get_curl_options(string $url) : array
    {
        return [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,

            // include header in output
            CURLOPT_HEADER         => false,
            // do not include body in output
            CURLOPT_NOBODY         => false,

            CURLOPT_FRESH_CONNECT  => false,

            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5,

            CURLOPT_VERBOSE        => false,
            // fail verbosely if the HTTP code returned is greater than or equal to 400
            CURLOPT_FAILONERROR    => false,

            // follow redirections
            CURLOPT_FOLLOWLOCATION => false,
            // how many redirections to follow
            CURLOPT_MAXREDIRS      => 0,
//            CURLOPT_ENCODING       => '', //'gzip, deflate',
//            CURLOPT_HTTPHEADER     => [
//                'Accept-Encoding: gzip, deflate',
//            ],

//            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/82.0.4058.0 Safari/537.36',

            // check the CA auth chain
            CURLOPT_SSL_VERIFYPEER => false,
            // check hostname/certname match
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
    }

    /**
     * Convert http code to string
     * @param  int    $code
     * @return string
     */
    public static function responseCode(int $code) : string
    {
        // https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        $responses = [
            200 => 'Success - OK',
            201 => 'Success - Created',
            202 => 'Success - Accepted',
            203 => 'Success - Non-Authoritative Information',
            204 => 'Success - No Content',
            205 => 'Success - Reset Content',
            206 => 'Success - Partial Content',
            207 => 'Success - Multi-Status',
            208 => 'Success - Already Reported',
            226 => 'Success - IM Used',

            300 => 'Redirect - Multiple Choices',
            301 => 'Redirect - Moved Permanently',
            302 => 'Redirect - Moved temporarily',
            303 => 'Redirect - See Other',
            304 => 'Redirect - Not Modified',
            305 => 'Redirect - Use Proxy',
            306 => 'Redirect - Switch Proxy',
            307 => 'Redirect - Temporary Redirect',
            308 => 'Redirect - Permanent Redirect',

            400 => 'Client error - Bad Request',
            401 => 'Client error - Unauthorized (RFC 7235)',
            402 => 'Client error - Payment Required',
            403 => 'Client error - Forbidden',
            404 => 'Client error - Not Found',
            405 => 'Client error - Method Not Allowed',
            406 => 'Client error - Not Acceptable',
            407 => 'Client error - Proxy Authentication Required (RFC 7235)',
            408 => 'Client error - Request Timeout',
            409 => 'Client error - Conflict',
            410 => 'Client error - Gone',
            411 => 'Client error - Length Required',
            412 => 'Client error - Precondition Failed (RFC 7232)',
            413 => 'Client error - Payload Too Large (RFC 7231)',
            414 => 'Client error - URI Too Long (RFC 7231)',
            415 => 'Client error - Unsupported Media Type (RFC 7231)',
            416 => 'Client error - Range Not Satisfiable (RFC 7233)',
            417 => 'Client error - Expectation Failed',
            418 => 'Client error - I\'m a teapot (RFC 2324, RFC 7168)',
            421 => 'Client error - Misdirected Request (RFC 7540)',
            422 => 'Client error - Unprocessable Entity (WebDAV; RFC 4918)',
            423 => 'Client error - Locked (WebDAV; RFC 4918)',
            424 => 'Client error - Failed Dependency (WebDAV; RFC 4918)',
            425 => 'Client error - Too Early (RFC 8470)',
            426 => 'Client error - Upgrade Required',
            428 => 'Client error - Precondition Required (RFC 6585)',
            429 => 'Client error - Too Many Requests (RFC 6585)',
            431 => 'Client error - Request Header Fields Too Large (RFC 6585)',
            451 => 'Client error - Unavailable For Legal Reasons (RFC 7725)',

            500 => 'Server error - Internal Server Error',
            501 => 'Server error - Not Implemented',
            502 => 'Server error - Bad Gateway',
            503 => 'Server error - Service Unavailable',
            504 => 'Server error - Gateway Timeout',
            505 => 'Server error - HTTP Version Not Supported',
            506 => 'Server error - Variant Also Negotiates (RFC 2295)',
            507 => 'Server error - Insufficient Storage (WebDAV; RFC 4918)',
            508 => 'Server error - Loop Detected (WebDAV; RFC 5842)',
            510 => 'Server error - Not Extended (RFC 2774)',
            511 => 'Server error - Network Authentication Required (RFC 6585)',
        ];

        if (array_key_exists($code, $responses))
            return $responses[$code];
        else
            return '????';
    }
}
