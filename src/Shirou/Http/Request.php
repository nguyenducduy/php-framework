<?php
namespace Shirou\Http;

use Phalcon\Http\Request as PhRequest;

class Request extends PhRequest
{
    protected function removeBearer($str)
    {
        return preg_replace('/.*\s/', '', $str);
    }

    public function getToken()
    {
        $authHeader = $this->getHeader('Authorization');
        $authQuery = $this->getQuery('token');

        return $authQuery ?? $this->removeBearer($authHeader);
    }
}
