<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class AdminFaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faqs = Faq::all();
        return apiSuccess("Faq's loaded successfully",$faqs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = new Faq();
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->updated_by = auth()->id();
        $faq->save();

        return apiSuccess("FAQ created successfully",$faq);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $faq = Faq::first();
        return apiSuccess("Faq's loaded successfully",$faq);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::find($id);
        if (!$faq) {
            return apiError("FAQ not found", 404);
        }
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->updated_by = auth()->id();
        $faq->save();

        return apiSuccess("FAQ updated successfully",$faq);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $faq = Faq::find($id);
        if (!$faq) {
            return apiError("FAQ not found", 404);
        }
        $faq->delete();

        return apiSuccess("FAQ deleted successfully");
    }
}
