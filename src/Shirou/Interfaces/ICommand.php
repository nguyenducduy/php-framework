<?php
namespace Shirou\Interfaces;

interface ICommand
{
    /**
     * Get command name.
     *
     * @return string
     */
    public function getName();

    /**
     * Prints help on the usage of the command.
     *
     * @return void
     */
    public function getHelp();

    /**
     * Dispatch command. Find out action and exec it with parameters.
     *
     * @return mixed
     */
    public function dispatch();
}
