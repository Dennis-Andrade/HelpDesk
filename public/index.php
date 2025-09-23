<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/../config/cnxn.php';
require __DIR__ . '/../app/Support/helpers.php';

// Autoload PSR-4 simple
spl_autoload_register(function ($class) {
  $map = [
    'App\\'    => __DIR__ . '/../app/',
    'Config\\' => __DIR__ . '/../config/'
  ];
  foreach ($map as $prefix => $base) {
    if (strpos($class, $prefix) === 0) {
      $rel = str_replace('\\', '/', substr($class, strlen($prefix)));
      $file = $base . $rel . '.php';
      if (is_file($file)) { require $file; return; }
    }
  }
});

// Helpers UI
function view(string $tpl, array $data=[]){
  extract($data);
  $file = __DIR__ . '/../app/Views/'.$tpl.'.php';
  $layoutName = isset($data['layout']) ? (string)$data['layout'] : 'layout'; // 'layout' (con sidebar) o 'auth'
  $layout = __DIR__ . '/../app/Views/layouts/'.$layoutName.'.php';
  if (!is_file($file))   { http_response_code(500); echo "Vista no encontrada"; return; }
  if (!is_file($layout)) { http_response_code(500); echo "Layout no encontrado"; return; }
  $___viewFile = $file;
  include $layout;
}

// ---------------- Router + Middleware ----------------
class Router {
  private $routes = ['GET'=>[], 'POST'=>[]];
  public function get(string $path, $handler, array $opts = []){ $this->routes['GET'][$path]=[$handler,$opts]; }
  public function post(string $path, $handler, array $opts = []){ $this->routes['POST'][$path]=[$handler,$opts]; }

  public function dispatch(){
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    if (!isset($this->routes[$m][$uri])) { http_response_code(404); echo "404"; return; }
    [$handler, $opts] = $this->routes[$m][$uri];
    $mwList = $opts['middleware'] ?? [];
    $callable = $this->toCallable($handler);
    $pipeline = MiddlewareKernel::pipeline($mwList, $callable);
    return $pipeline();
  }

  private function toCallable($h): callable {
    if (is_array($h) && is_string($h[0])) { // [Class, method]
      $obj = new $h[0]();
      return function() use ($obj, $h){ return call_user_func([$obj, $h[1]]); };
    }
    if (is_callable($h)) return $h;
    throw new \RuntimeException('Handler inválido');
  }
}

// Kernel de middleware
final class MiddlewareKernel {
  /** @return callable */
  public static function pipeline(array $mwNames, callable $last): callable {
    $stack = array_reverse($mwNames);
    $next = $last;
    foreach ($stack as $name) {
      $mw = \App\Middleware\Registry::make($name);
      $cur = $next;
      $next = function() use ($mw, $cur) { return $mw->handle($cur); };
    }
    return $next;
  }
}

$router = new Router();
require __DIR__ . '/../config/routes.php';
$router->dispatch();
