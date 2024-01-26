<?php

namespace Mantonio84\ApiLogger\Http\Middleware;

use Mantonio84\ApiLogger\Contracts\ApiLoggerInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiLogger
{
    protected $logger;

    public function __construct(ApiLoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param  \Closure                $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        return $response;
    }

    public function terminate(Request $request,  $response) {
		if (config("apilog.enabled") === true){
			$this->logger->saveLogs($request, $response);
		}
    }
}
