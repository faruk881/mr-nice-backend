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
        $document = $request->route('document');

        if (!$document || $document->status !== $status) {
            return response()->json([
                'success' => false,
                'message' => "This action requires the document to be: " . ucfirst($status),
                'current_status' => $document?->status
            ], 403);
        }
        return $next($request);
    }
}
