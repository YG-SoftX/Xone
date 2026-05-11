<?php

namespace App\Http\Controllers;

use App\Models\UniversalNavigationItem;
use Illuminate\Http\Request;

class UniversalNavigationController extends Controller
{
    /**
     * Display a listing of navigation items.
     */
    public function index(Request $request)
    {
        $service = $request->get('service', 'account');
        $items = UniversalNavigationItem::forService($service)
            ->orderBy('position')
            ->orderBy('order')
            ->get();

        return view('admin.navigation.index', compact('items', 'service'));
    }

    /**
     * Store a new navigation item.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_key' => 'required|string',
            'position' => 'required|in:header,footer,sidebar',
            'label' => 'required|string|max:255',
            'url' => 'required|string',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer',
        ]);

        UniversalNavigationItem::create($request->all());

        return redirect()->back()->with('success', 'Navigation Authority Established.');
    }

    /**
     * Update a navigation item.
     */
    public function update(Request $request, UniversalNavigationItem $item)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'url' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $item->update($request->all());

        return redirect()->back()->with('success', 'Navigation Authority Updated.');
    }

    /**
     * Remove a navigation item.
     */
    public function destroy(UniversalNavigationItem $item)
    {
        $item->delete();
        return redirect()->back()->with('success', 'Navigation Authority Rescinded.');
    }
}
