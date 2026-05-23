<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const STORE_KEYS = [
        'store_name', 'store_tagline', 'store_address',
        'store_phone', 'store_email', 'receipt_footer', 'pos_class',
        'pos_product_display',
    ];

    public function index()
    {
        $settings = [];
        foreach (self::STORE_KEYS as $key) {
            $settings[$key] = Setting::get($key, '');
        }

        return view('settings.index', compact('settings'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'store_name'     => 'required|string|max:100',
            'store_tagline'  => 'nullable|string|max:150',
            'store_address'  => 'nullable|string|max:300',
            'store_phone'    => 'nullable|string|max:30',
            'store_email'    => 'nullable|email|max:100',
            'receipt_footer' => 'nullable|string|max:200',
            'pos_class'           => 'nullable|string|max:100',
            'pos_product_display' => 'nullable|in:image,text',
        ]);

        foreach (self::STORE_KEYS as $key) {
            Setting::set($key, $request->input($key, ''), 'store');
        }

        return response()->json(['success' => true]);
    }

    // Helper yang dipanggil oleh PosController
    public static function storeSettings(): array
    {
        return [
            'store_name'     => Setting::get('store_name', 'HPYSync'),
            'store_tagline'  => Setting::get('store_tagline', 'Point of Sale System'),
            'store_address'  => Setting::get('store_address', ''),
            'store_phone'    => Setting::get('store_phone', ''),
            'store_email'    => Setting::get('store_email', ''),
            'receipt_footer' => Setting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!'),
            'pos_class'           => Setting::get('pos_class', ''),
            'pos_product_display' => Setting::get('pos_product_display', 'image'),
        ];
    }
}
