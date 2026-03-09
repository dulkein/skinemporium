<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellController extends Controller
{
    /**
     * Show the basic sell form.
     */
    public function create()
    {
        return view('sell.create');
    }

    /**
     * Validate and save submitted form data in flash session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:100'],
            'wear' => ['required', 'string', 'max:40'],
            'float_value' => ['required', 'numeric', 'min:0', 'max:1'],
            'price_usd' => ['required', 'numeric', 'min:0.5'],
            'trade_link' => ['required', 'url', 'max:255'],
        ]);

        return redirect()
            ->route('sell.success')
            ->with('submitted_listing', $validated);
    }

    /**
     * Show a success page after form submission.
     */
    public function success(Request $request)
    {
        $listing = $request->session()->get('submitted_listing');

        if (! $listing) {
            return redirect()->route('sell.create');
        }

        return view('sell.success', [
            'listing' => $listing,
        ]);
    }
}
