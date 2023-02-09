<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Packages\Database\Contracts;

use Drewlabs\Contracts\Support\Actions\Action;

interface QueryLanguageCommandInterface
{
    /**
     * Execute the DML Query action command on developper provided action interface.
     *
     * @param ActionsInterface $action
     *
     * @return ActionResultInterface
     */
    public function __invoke(Action $action, ?\Closure $closure = null);

    /**
     * Execute the DML Query action command on developper provided action interface.
     *
     * @param ActionsInterface $action
     *
     * @return ActionResultInterface
     */
    public function exec(Action $action, ?\Closure $closure = null);
}
