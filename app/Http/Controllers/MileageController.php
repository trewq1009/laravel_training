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
            $userData = DB::table('tr_mileage_detail')->where('user_no', Auth::user()->no)->first();

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

            $withdrawalNo = DB::table('tr_withdrawal')->insertGetId([
                'user_no' => Auth::user()->no,
                'withdrawal_mileage' => $validated['withdrawalMileage'],
                'bank_name' => $validated['bankValue'],
                'bank_account_number' => Crypt::encryptString($validated['bankNumber'])
            ]);
            if(!$withdrawalNo) {
                $validator->errors()->add('withdrawalMileage', '출금신청 에러');
                throw new DatabaseException();
            }

            $userDetailModel = DB::table('tr_mileage_detail')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$userDetailModel) {
                $validator->errors()->add('withdrawalMileage', '정보를 불러올수 없습니다.');
                throw new DatabaseException();
            }
            if($userDetailModel->real_mileage < $validated['withdrawalMileage']) {
                $validator->errors()->add('withdrawalMileage', '출글할 수 있는 마일리지를 넘었습니다.');
                throw new DatabaseException();
            }

            $userDetailUpdateRow = DB::table('tr_mileage_detail')->where('user_no', Auth::user()->no)->update([
                'real_mileage' => $userDetailModel->real_mileage - $validated['withdrawalMileage'],
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$userDetailUpdateRow) {
                $validator->errors()->add('withdrawalMileage', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $userMileageModel = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$userMileageModel) {
                $validator->errors()->add('withdrawalMileage', '마일리지 로드 에러');
                throw new DatabaseException();
            }
            $totalMileage = $userDetailModel->real_mileage + $userDetailModel->event_mileage;
            if($totalMileage !== $userMileageModel->mileage) {
                $validator->errors()->add('withdrawalMileage', '마일리지 무결성 에러');
                throw new DatabaseException();
            }

            $userMileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                'mileage' => $userDetailModel->real_mileage - $validated['withdrawalMileage'],
                'using_mileage' => $userMileageModel->using_mileage + $validated['withdrawalMileage'],
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$userMileageUpdateRow) {
                $validator->errors()->add('withdrawalMileage', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $userMileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => Auth::user()->no, 'method' => 'withdrawal', 'method_no' => $withdrawalNo,
                'before_mileage' => $userMileageModel->mileage,
                'use_mileage' => $validated['withdrawalMileage'],
                'after_mileage' => $userMileageModel->mileage - $validated['withdrawalMileage'],
                'real_mileage_usage' => $validated['withdrawalMileage']
            ]);
            if(!$userMileageLogNo) {
                $validator->errors()->add('withdrawalMileage', '로그 저장 에러');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator)->withInput();
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
