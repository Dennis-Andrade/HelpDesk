<?php
namespace App\Middleware;

interface Middleware {
  /** @param callable $next */
  public function handle(callable $next);
}
