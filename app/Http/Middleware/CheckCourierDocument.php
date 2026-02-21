<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCourierDocument
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $status): Response
    {
        // Laravel automatically resolves the {document} ID into a Model instance
        $document = $request->user()->courierProfile()->first();

        if (!$document || $document->document_status !== $status) {

            return apiError('Unauthorized. Documents not ' . $status,403, ['document_status' => $document->document_status]);
        }
        return $next($request);
    }
}
