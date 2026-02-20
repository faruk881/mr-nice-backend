<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Term;
use Illuminate\Http\Request;

class AdminTermsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $terms = Term::first();
        return apiSuccess("Terms loaded successfully",$terms);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $terms = Term::first();
        if (!$terms) {
            $terms = new Term();
        }
        $terms->content = $request->content;
        $terms->updated_by = auth()->id();
        $terms->save();

        return apiSuccess("Terms updated successfully",$terms);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
