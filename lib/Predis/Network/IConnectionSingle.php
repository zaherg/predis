<?php

namespace Predis\Network;

use Predis\ICommand;

interface IConnectionSingle extends IConnection {
    public function __toString();
    public function getResource();
    public function getParameters();
    public function setProtocolOption($option, $value);
    public function pushInitCommand(ICommand $command);
    public function read();
}