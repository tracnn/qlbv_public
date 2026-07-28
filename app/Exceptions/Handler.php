<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Bo sung ngu canh request vao moi dong log loi.
     *
     * Ly do: loi fatal cua PHP (het bo nho, qua thoi gian chay) duoc Laravel bat o
     * shutdown handler nen stacktrace chi con "#0 {main}" — khong the biet request
     * nao gay ra. Ghi them URL/method/tham so de truy nguoc duoc.
     */
    protected function context()
    {
        $context = parent::context();

        try {
            $request = request();
            if ($request) {
                $context['url']    = $request->fullUrl();
                $context['method'] = $request->method();
                $context['route']  = $request->route() ? $request->route()->getName() : null;
                // Dung all(): request co the chua file upload hoac payload rat lon.
                $context['query']  = $request->query();
                $context['mem_peak_mb'] = round(memory_get_peak_usage(true) / 1048576, 1);
            }
        } catch (\Throwable $e) {
            // Ghi log khong duoc phep lam hong luong bao loi.
        }

        return array_filter($context, function ($v) {
            return $v !== null && $v !== [];
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        return parent::render($request, $exception);
    }
}
