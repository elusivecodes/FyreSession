<?php
declare(strict_types=1);

use Fyre\Session\Session;
use Tests\Mock\MockSessionHandler;

Session::register([
    'className' => MockSessionHandler::class,
]);
