<?php

namespace App\Http\Controllers;

use App\Exceptions\DatabaseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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
            return redirect('/admin');

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/');
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function list(Request $request)
    {
        try {

            return view('admin.member.list',['data' => DB::table('tr_account')->orderByDesc('no')->paginate(10)]);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function detail(Request $request, $no)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userNo' => ['required', 'integer']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            if($validated['userNo'] !== $no) {
                throw new Exception();
            }

            $userModel = DB::table('tr_account')->where('no', $validated['userNo'])->first();
            if(!$userModel) {
                throw new Exception();
            }

            $userModel->name = Crypt::decryptString($userModel->name);
            $userModel->email = Crypt::decryptString($userModel->email);
            $userModel->email_status = $userModel->email_status === 't' ? '이메일 인증 완료' : '이메일 미인증';
            if($userModel->status === 't') {
                $userModel->status_kr = '일반회원';
            } else if($userModel->status === 'a') {
                $userModel->status_kr = '탈퇴신청회원';
            } else {
                $userModel->status_kr = '탈퇴회원';
            }

            $userMileage = DB::table('tr_mileage')->where('user_no', $validated['userNo'])->first();
            if(!$userMileage) {
                throw new Exception();
            }
            $userDetailModel = DB::table('tr_mileage_detail')->where('user_no', $validated['userNo'])->first();
            if(!$userDetailModel) {
                throw new Exception();
            }
            if($userMileage->mileage !== $userDetailModel->real_mileage + $userDetailModel->event_mileage) {
                throw new Exception();
            }

            $userModel->using_mileage = $userMileage->using_mileage;
            $userModel->real_mileage = $userDetailModel->real_mileage;
            $userModel->event_mileage = $userDetailModel->event_mileage;



            return view('admin.member.detail', ['data' => $userModel]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function withdrawalList(Request $request)
    {
        try {

            $paginate = DB::table('tr_withdrawal')->where('status', 'a')
                ->orderByDesc('no')->paginate(10);
            $data = (object)$paginate;
            $data = json_encode($data);
            $data = json_decode($data);

            foreach ($data->data as $key => $value) {
                $userModel = DB::table('tr_account')->where('no', $value->user_no)->first();
                $data->data[$key]->id = $userModel->id;
            }

            return view('admin.withdrawal.list', ['data' => $data, 'page' => $paginate]);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function withdrawalDetail(Request $request, $no)
    {
        try {

            $withdrawalModel = DB::table('tr_withdrawal')->where('no', $no)->first();
            if(!$withdrawalModel) {
                throw new Exception();
            }

            $withdrawalModel->bank_account_number = Crypt::decryptString($withdrawalModel->bank_account_number);

            $userModel = DB::table('tr_account')->where('no', $withdrawalModel->user_no)->first();
            if(!$userModel) {
                throw new Exception();
            }

            $withdrawalModel->user_id = $userModel->id;
            $withdrawalModel->user_name = Crypt::decryptString($userModel->name);

            return view('admin.withdrawal.detail', ['data' => $withdrawalModel]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function withdrawalAction(Request $request, $no)
    {
        try {
            $validator = Validator::make($request->all(), [
                'withdrawalNo' => ['required', 'integer']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();
            if($no !== $validated['withdrawalNo']) {
                throw new Exception();
            }

            DB::beginTransaction();

            $withdrawalModel = DB::table('tr_withdrawal')->where('no', $no)->lockForUpdate()->first();
            if(!$withdrawalModel) {
                throw new Exception();
            }
            if($withdrawalModel->status !== 'a') {
                throw new Exception();
            }

            $withdrawalUpdateRow = DB::table('tr_withdrawal')->where('no', $no)->update([
                'status' => 't',
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$withdrawalUpdateRow) {
                throw new Exception();
            }

            $userMileageModel = DB::table('tr_mileage')
                ->where('user_no', $withdrawalModel->user_no)->lockForUpdate()->first();
            if(!$userMileageModel) {
                throw new Exception();
            }
            if($userMileageModel->using_mileage < $withdrawalModel->withdrawal_mileage) {
                throw new Exception();
            }

            $userMileageUpdateRow = DB::table('tr_mileage')->where('user_no', $withdrawalModel->user_no)->update([
                'using_mileage' => $userMileageModel->using_mileage - $withdrawalModel->withdrawal_mileage,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$userMileageUpdateRow) {
                throw new Exception();
            }

            DB::commit();
            return redirect('/admin/withdrawal/list');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back();
        } catch (Exception $e) {
            return redirect()->back();
        }
    }
}
