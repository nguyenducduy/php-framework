<?php
namespace Shirou\Interfaces;

use Phalcon\DiInterface;

interface IService
{
    public function __construct(DiInterface $di, $arguments);
}
