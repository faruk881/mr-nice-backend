<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;

class AdminPrivacyController extends Controller
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
        $privacyPolicy = PrivacyPolicy::first();
        return apiSuccess("Privacy Policy loaded successfully",$privacyPolicy);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $privacyPolicy = PrivacyPolicy::first();
        if (!$privacyPolicy) {
            $privacyPolicy = new PrivacyPolicy();
        }
        $privacyPolicy->content = $request->content;
        $privacyPolicy->updated_by = auth()->id();
        $privacyPolicy->save();

        return apiSuccess("Privacy Policy updated successfully",$privacyPolicy);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
