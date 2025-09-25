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
  private $routes = ['GET' => [], 'POST' => []];

  public function get($path, $handler, array $opts = array()) {
    $this->addRoute('GET', $path, $handler, $opts);
  }

  public function post($path, $handler, array $opts = array()) {
    $this->addRoute('POST', $path, $handler, $opts);
  }

  private function addRoute($method, $path, $handler, $opts) {
    $this->routes[$method][$path] = array($handler, $opts);
  }

  public function dispatch() {
    $m   = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $uri = strtok(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', '?');
    if (!$uri) { $uri = '/'; }

    $match = $this->matchRoute($m, $uri);
    if ($match === null) { http_response_code(404); echo "404"; return; }

    list($handler, $opts, $params) = $match;
    $mwList   = isset($opts['middleware']) ? $opts['middleware'] : array();
    $callable = $this->toCallable($handler);
    $pipeline = MiddlewareKernel::pipeline($mwList, function() use ($callable, $params) {
      return call_user_func_array($callable, $params);
    });
    return $pipeline();
  }

  private function toCallable($h) {
    if (is_array($h) && is_string($h[0])) { // [Class, method]
      $obj = new $h[0]();
      return array($obj, $h[1]);
    }
    if (is_callable($h)) { return $h; }
    throw new \RuntimeException('Handler inválido');
  }

  /** @return array|null [$handler, $opts, $params] */
  private function matchRoute($method, $uri) {
    if (isset($this->routes[$method][$uri])) {
      list($handler, $opts) = $this->routes[$method][$uri];
      return array($handler, $opts, array());
    }

    foreach ($this->routes[$method] as $path => $info) {
      $params = $this->matchDynamic($path, $uri);
      if ($params !== null) {
        list($handler, $opts) = $info;
        return array($handler, $opts, $params);
      }
    }

    return null;
  }

  /** Convierte '/foo/{id}/bar' a regex y devuelve params o null (PHP 7 compatible) */
  private function matchDynamic($pattern, $uri) {
    if (strpos($pattern, '{') === false) { return null; }

    $regex = preg_quote($pattern, '#');
    $regex = preg_replace('#\\\{([^/]+)\\\}#', '([^/]+)', $regex);
    if ($regex === null) { return null; }

    if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
      array_shift($matches);
      return $matches; // params ordenados
    }
    return null;
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
