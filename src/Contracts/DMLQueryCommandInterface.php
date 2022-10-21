<?php

namespace Drewlabs\Packages\Database\Contracts;

use Drewlabs\Contracts\Support\Actions\Action;

interface DMLQueryCommandInterface
{
    /**
     * Execute the DML Query action command on developper provided action interface
     * 
     * @param ActionsInterface $action 
     * @param \Closure|null $callback
     * @return ActionResultInterface 
     */
    public function __invoke(Action $action, ?\Closure $closure = null);

    /**
     * Execute the DML Query action command on developper provided action interface
     * 
     * @param ActionsInterface $action 
     * @param \Closure|null $callback
     * @return ActionResultInterface 
     */
    public function exec(Action $action, ?\Closure $closure = null);
}
