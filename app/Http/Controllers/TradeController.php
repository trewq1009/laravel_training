<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TradeController extends Controller
{
    public function list()
    {
        try {

            return view('trade.list', ['data' => DB::table('tr_trade_board')->where('status', 't')->orderByDesc('no')->paginate(10)]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

}
