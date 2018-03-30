<?php
namespace Shirou\Http;

use Phalcon\Http\Response as PhResponse;

class Response extends PhResponse
{
    protected $statusCode = 200;
    protected $code = 0;

    protected function parseTrace($traces)
    {
        $parsed = [];

        foreach ($traces as $key => $trace) {
            $parsed[$key] = $trace;

            if (is_object($trace)) {
                $parsed[$key] = 'closure...';
            }

            if (is_array($trace)) {
                $parsed[$key] = $this->parseTrace($trace);
            }
        }

        return $parsed;
    }

    public function sendErrorMessage($e)
    {
        $code = $e->getCode();

        if ($e instanceof \ErrorException
            || is_a($e, '\Phalcon\Mvc\Dispatcher\Exception')
            || $e instanceof \PDOException
            || $e instanceof \Error
            || $e instanceof \Phalcon\Di\Exception
            || $e instanceof \Exception
        ) {
            $message = $e->getMessage();
            $this->statusCode = 500;
        }

        if ($e instanceof \Shirou\UserException) {
            $message = $this->getDI()->get('lang')->_($this->getMessage($code));
            $this->statusCode = $this->getStatusCode($code);
        }

        $error = [
            'data' => [],
            'errors' => [
                'code' => $code,
                'status' => $this->statusCode,
                'message' => $message,
            ]
        ];

        $error['developer'] = [
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'message' => $e->getMessage(),
        ];

        // include trace
        $error['developer']['trace'] = $this->parseTrace($e->getTrace());

        return $this->_send($error);
    }

    public function _send($data)
    {
        $this->setHeader('Access-Control-Allow-Origin', '*');
        $this->setHeader('Access-Control-Allow-Methods', 'GET,HEAD,POST,PUT,DELETE,OPTIONS');
        $this->setHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Requested-With, Authorization');
        $this->setStatusCode($this->statusCode, $this->getStatusMessage($this->statusCode));
        $this->setContentType('application/json', 'UTF-8');
        $this->setJsonContent($data);

        return $this->send();
    }

    private function getStatusMessage(int $statusCode)
    {
        $codes = [
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found', // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        ];

        return (isset($codes[$statusCode])) ? $codes[$statusCode] : 'Unknown Status Code';
    }

    public function getStatusCode($code): int
    {
        return $this->getDI()->get('config')->code->{$code}->status;
    }

    public function getMessage($code): string
    {
        return $this->getDI()->get('config')->code->{$code}->message;
    }
}
