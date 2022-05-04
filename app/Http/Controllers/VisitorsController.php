<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use App\Exceptions\DatabaseException;

class VisitorsController extends Controller
{
    public function list()
    {
        try {
            return view('visitors.list', ['data' => DB::table('tr_visitors_board')->where('status', 't')->orderByDesc('no')->paginate(10)]);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function insert(Request $request)
    {
        try {
            if(Auth::check()) {
                $validator =  Validator::make($request->all(),['content' => ['required']]);
            } else {
                $validator =  Validator::make($request->all(),['content' => ['required'], 'visitorsPassword' => ['required', 'alpha_num']]);
            }

            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            if(Auth::check()) {
                $params = ['user_type'=>'m', 'user_no'=>Auth::user()->no, 'user_name'=>'유저', 'content'=>$validated['content']];
            } else {
                $params = ['user_type'=>'g', 'visitors_password'=>Hash::make($validated['visitorsPassword']), 'content'=>$validated['content']];
            }

            DB::beginTransaction();

            $boardNo = DB::table('tr_visitors_board')->insertGetId($params);
            if(!$boardNo) {
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/visitors');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back();
        } catch (Exception $e) {
            return redirect()->back();
        }
    }
}
