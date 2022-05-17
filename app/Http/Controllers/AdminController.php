<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'adminId' => ['required', 'alpha_num'],
                'adminPw' => ['required', 'min:8']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();
            $userData['id'] = $validated['adminId'];
            $userData['password'] = $validated['adminPw'];

            $userModel = DB::table('tr_account_admin')->where('status', 't')->where('id', $userData['id'])->first();
            if(!$userModel) {
                throw new Exception();
            }
            if (!Auth::guard('admin')->attempt($userData)) {
                throw new Exception();
            }

            $request->session()->regenerate();
            return redirect()->intended();

        } catch (Exception $e) {
            return redirect()->back();
        }
    }
}