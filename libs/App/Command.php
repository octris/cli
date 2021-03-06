<?php

/*
 * This file is part of the 'octris/cli' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Cli\App;

/**
 * Proxy class for extending aaparser command class.
 *
 * @copyright   copyright (c) 2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Command
{
    /**
     * Instance of application the command belongs to.
     *
     * @type    \Octris\Cli\App
     */
    protected $app;

    /**
     * Command instance.
     *
     * @type    \Aaparser\Command
     */
    protected $command;

    /**
     * Constructor.
     *
     * @param   \Octris\Cli\App         $app                Instance of application the command belongs to.
     * @param   \Aaparser\Command       $command            Instance of a command.
     */
    public function __construct(\Octris\Cli\App $app, \Aaparser\Command $command)
    {
        $this->app = $app;
        $this->command = $command;
    }

    /**
     * Define a new command.
     *
     * @param   string      $name           Name of command.
     * @param   array       $settings       Optional additional settings.
     * @return  \Octris\Cli\App\Command     Instance of new command.
     */
    public function addCommand($name, array $settings = [])
    {
        $instance = new Command($this->app, $this->command->addCommand($name, $settings));

        return $instance;
    }

    /**
     * Import and add a command from a php script.
     *
     * @param   string      $name               Name of command to import.
     * @param   string      $class              Name of class to import command from, must implent \Octris\App\CommandInterface.
     * @param   array       $inject             Arguments to inject into command instance.
     * @param   callable    $default_command    Optional default command (default: help).
     * @return  \Aaparser\Command               Instance of new object.
     */
    public function importCommand($name, $class, array $inject = [], callable $default_command = null)
    {
        if (!is_subclass_of($class, '\Octris\Cli\App\CommandInterface')) {
            throw new \InvalidArgumentException('Class is not a valid command "' . $class . '".');
        }

        $cmd = $this->addCommand($name);
        $cmd->setAction(function(array $options, array $operands) use ($class, $inject) {
            $instance = new $class(...$inject);
            $instance->run($options, $operands, new Output());
        });

        if (is_null($default_command)) {
            $default_command = function() use ($cmd) {
                $this->app->printHelp($cmd);
            };
        }

        $cmd->setDefaultAction($default_command);

        $class::configure($cmd);

        return $cmd;
    }

    /**
     * Method proxy.
     *
     * @param   string                  $name               Name of method to call.
     * @param   array                   $args               Arguments for method.
     * @return  mixed                                       Return value of proxied method.
     */
    public function __call($name, array $args)
    {
        return $this->command->{$name}(...$args);
    }
}
