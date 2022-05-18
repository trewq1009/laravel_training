<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Exception;
use App\Exceptions\DatabaseException;

class MileageController extends Controller
{
    public function withdrawal()
    {
        try {
            $userData = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->first();

            $viewData = [
                'real_mileage' => $userData->real_mileage
            ];

            return view('mileage.withdrawal', $viewData);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function withdrawalAction(Request $request)
    {
        try {
            $validator =  Validator::make($request->all(),[
                'withdrawalMileage' => ['required','numeric','between:1000,9999999'],
                'bankValue' => ['required','alpha'],
                'bankNumber' => ['required','numeric']
            ]);

            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            DB::beginTransaction();

            $userMileageData = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->lockForUpdate()->first();

            $withdrawalLogNo = DB::table('tr_withdrawal_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'withdrawal_mileage' => $validated['withdrawalMileage'],
                'bank_name' => $validated['bankValue'],
                'bank_account_number' => Crypt::encryptString($validated['bankNumber']),
                'status' => 'a'
            ]);
            if(!$withdrawalLogNo) {
                throw new DatabaseException();
            }

            $mileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => 'withdrawal',
                'method_no' => $withdrawalLogNo,
                'before_mileage' => $userMileageData->use_mileage,
                'use_mileage' => $validated['withdrawalMileage'],
                'after_mileage' => $userMileageData->use_mileage - $validated['withdrawalMileage']
            ]);
            if(!$mileageLogNo) {
                throw new DatabaseException();
            }

            $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                'using_mileage' => $userMileageData->using_mileage + $validated['withdrawalMileage'],
                'use_mileage' => $userMileageData->use_mileage - $validated['withdrawalMileage'],
                'real_mileage' => $userMileageData->real_mileage - $validated['withdrawalMileage']
            ]);
            if(!$mileageUpdateRow) {
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect('/');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    public function report()
    {
        try {

            return view('mileage.report', [
                'list' => DB::table('tr_mileage_log')->where('user_no', Auth::user()->no)
                    ->orderByDesc('no')->simplePaginate(10)
            ]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }


}
