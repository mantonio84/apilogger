<?php

namespace Mantonio84\ApiLogger;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Psy\Util\Json;

abstract class AbstractLogger{

    protected $logs = [];

    protected $models = [];

    public function __construct()
    {
        $this->boot();
    }
    /**
     * starting method just for cleaning code
     *
     * @return void
     */
    public function boot(){
		if (config("apilog.enabled") === true){
			Event::listen('eloquent.*', function ($event, $models) {
				if (Str::contains($event, 'eloquent.retrieved')) {
					foreach (array_filter($models) as $model) {
						$class = get_class($model)."@".$model->getKey();
						$this->models[$class] = ($this->models[$class] ?? 0) + 1;
					}
				}
			});
		}
    }

    /**
     * logs into associative array
     *
     * @param Request                                $request
     * @param Response|JsonResponse|RedirectResponse $response
     *
     * @return array
     */
    public function logData(Request $request,  $response){
        $currentRouteAction = Route::currentRouteAction();

        // Initialiaze controller and action variable before use them
        $controller = "";
        $action = "";

        /*
         * Some routes will not contain the `@` symbole (e.g. closures, or routes using a single action controller).
         */
        if ($currentRouteAction) {
            if (strpos($currentRouteAction, '@') !== false) {
                [$controller, $action] = explode('@', $currentRouteAction);
            } else {
                // If we get a string, just use that.
                if (is_string($currentRouteAction)) {
                    [$controller, $action] = ["", $currentRouteAction];
                } else {
                    // Otherwise force it to be some type of string using `json_encode`.
                    [$controller, $action] = ["", (string)json_encode($currentRouteAction)];
                }
            }
        }

        $endTime = microtime(true);

        $models = $this->models;
		ksort($models);
        array_walk($models, function(&$value, $key) {
            $value = "{$key} ({$value})";
        });
					
		
		
        $this->logs['created_at'] = Carbon::now();
        $this->logs['method'] = $request->method();
        $this->logs['url'] = $request->path();
        $this->logs['payload'] = $this->payload($request);
        $this->logs['payload_raw'] = config('apilog.payload_raw', false) ? file_get_contents('php://input') : null;
        $this->logs['headers'] = $this->headers($request);
        $this->logs['status_code'] = $response->status();
        $this->logs['response'] = $response->getContent();
        $this->logs['response_headers'] = $this->headers($response);
        $this->logs['duration'] = number_format($endTime - LARAVEL_START, 3);
        $this->logs['controller'] = $controller;
        $this->logs['action'] = $action;
        $this->logs['models'] = $models;
        $this->logs['ip'] = $request->ip();

        return $this->logs;
    }


    /**
     * Formats the request payload for logging
     *
     * @param $request
     * @return string
     */
    protected function payload($request)
    {
        $allFields = $request->all();
		
		$this->maskDontLog($allFields,config('apilog.dont_log', []));

        return json_encode($allFields);
    }

    /**
     * Formats the headers payload for logging
     *
     * @param $request
     * @return string
     */
    protected function headers($request)
    {
        $allHeaders = $request->headers->all();
		
		$this->maskDontLog($allHeaders,config('apilog.dont_log_headers', []));
        
        return json_encode($allHeaders);
    }
	
	protected function maskDontLog(array &$source, array $dont_log) {
		if (!empty($dont_log)){
			Arr::forget($source,$dont_log);
		}
	}
}
