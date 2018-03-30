<?php
namespace Shirou\Interfaces;

use Phalcon\{
    DI,
    Events\Manager as PhEventsManager
};

interface IBootstrap
{
    public function __construct(DI $di, PhEventsManager $em);

    public function registerServices();
}
