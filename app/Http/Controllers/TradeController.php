<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Exceptions\DatabaseException;

class TradeController extends Controller
{
    public function list()
    {
        try {
            return view('trade.list', [
                'data' => DB::table('tr_trade_board')->where('status', 't')->orderByDesc('no')->paginate(10),
                'auth' => Auth::user()
            ]);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function insert(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'productName' => ['required'],
                'imageInfo' => ['required', 'image'],
                'productPrice' => ['required', 'integer', 'min:1000'],
                'productInformation' => ['required']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            $randDate = date('mdhis', time());
            $fileExp = $request->file('imageInfo')->extension();
            $newFileName = chr(rand(97,122)).chr(rand(97,122)).$randDate.".$fileExp";
            $request->file('imageInfo')->storeAs('images', $newFileName,'public');

            DB::beginTransaction();

            $tradNo = DB::table('tr_trade_board')->insertGetId([
                'user_no' =>Auth::user()->no,
                'product_name' => $validated['productName'],
                'product_information' => $validated['productInformation'],
                'price' => $validated['productPrice'],
                'status' => 't'
            ]);
            if(!$tradNo) {
                throw new DatabaseException();
            }

            $imageNo = DB::table('tr_image')->insertGetId([
                'method' => 'trade',
                'reference_no' => $tradNo,
                'image_name' => $newFileName,
                'status' => 't'
            ]);
            if(!$imageNo) {
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/trade');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator)->withInput();
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    }

    public function detail($no)
    {
        try {
            $boardModel = DB::table('tr_trade_board')->where('status', 't')->where('no', $no)->first();
            $imageModel = DB::table('tr_image')->where('status', 't')->where('method', 'trade')->where('reference_no', $no)->first();

            return view('trade.detail', ['board' => $boardModel, 'image' => $imageModel, 'auth' => Auth::user()]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function trading(Request $request, $no)
    {
        try {
            $validator = Validator::make($request->all(), ['boardNo' => ['required', 'integer']]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            if($no !== $validated['boardNo']) {
                $validator->errors()->add('boardNo', '잘못된 접근 입니다.');
                throw new Exception();
            }

            DB::beginTransaction();

            $boardModel = DB::table('tr_trade_board')->where('status', 't')->where('no', $validated['boardNo'])->first();

            if($boardModel->user_no === Auth::user()->no) {
                $validator->errors()->add('boardNo', '본인글에 신청 하셨습니다.');
                throw new Exception();
            }

            $buyerMileageModel = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if($boardModel->price > $buyerMileageModel->use_mileage) {
                $validator->errors()->add('boardNo', '마일리지가 부족합니다.');
                throw new Exception();
            }
            $calcMileage = $buyerMileageModel->use_mileage - $boardModel->price;
            if($calcMileage < $buyerMileageModel->real_mileage) {
                $params = ['use_mileage' => $calcMileage, 'real_mileage' => $calcMileage, 'using_mileage' => $buyerMileageModel->using_mileage + $boardModel->price, 'update_date' => date('Y-m-d H:i:s')];
            } else {
                $params = ['use_mileage' => $calcMileage, 'using_mileage' => $buyerMileageModel->using_mileage + $boardModel->price, 'update_date' => date('Y-m-d H:i:s')];
            }

            $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update($params);
            if(!$mileageUpdateRow) {
                $validator->errors()->add('boardNo', '마일리지 사용에 실패했습니다.');
                throw new DatabaseException();
            }

            $tradeLog = DB::table('tr_trade_log')->insertGetId([
                'trade_board_no' => $validated['boardNo'],
                'seller_no' => $boardModel->user_no,
                'buyer_no' => Auth::user()->no,
                'trade_price' => $boardModel->price
            ]);
            if(!$tradeLog) {
                $validator->errors()->add('boardNo', '로그 저장에 실패했습니다.');
                throw new DatabaseException();
            }

            $mileageLog = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => Auth::user()->no,
                'method' => 'trade',
                'method_no' => $tradeLog,
                'before_mileage' => $buyerMileageModel->use_mileage,
                'use_mileage' => $boardModel->price,
                'after_mileage' => $calcMileage
            ]);
            if(!$mileageLog) {
                $validator->errors()->add('boardNo', '로그 저장에 실패했습니다.');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/trade/list');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator);
        }
    }

    public function tradeList()
    {
        try {

            $pagination = DB::table('tr_trade_log')->where('seller_no', Auth::user()->no)->orWhere('buyer_no', Auth::user()->no)->orderByDesc('no')->paginate(10);
            $paging = (object)$pagination;
            $paging = json_encode($paging);
            $paging = json_decode($paging);

            foreach ($paging->data as $key => $value) {
                if($value->seller_no === Auth::user()->no) {
                    $paging->data[$key]->trade_name = '판매';

                    if($value->seller_trade_status === 'a') {
                        $paging->data[$key]->me_status = '진행중';
                    } else {
                        $paging->data[$key]->me_status = '거래완료';
                    }
                    if($value->buyer_trade_status === 'a') {
                        $paging->data[$key]->user_status = '진행중';
                    } else {
                        $paging->data[$key]->user_status = '거래완료';
                    }

                } else {
                    $paging->data[$key]->trade_name = '구매';

                    if($value->seller_trade_status === 'a') {
                        $paging->data[$key]->user_status = '진행중';
                    } else {
                        $paging->data[$key]->user_status = '거래완료';
                    }
                    if($value->buyer_trade_status === 'a') {
                        $paging->data[$key]->me_status = '진행중';
                    } else {
                        $paging->data[$key]->me_status = '거래완료';
                    }
                }

                $boardModel = DB::table('tr_trade_board')->where('no', $value->trade_board_no)->first();
                $paging->data[$key]->product_name = $boardModel->product_name;

                if($value->status === 'a') {
                    $paging->data[$key]->status_kr = '진행중';
                } else if($value->status === 'c') {
                    $paging->data[$key]->status_kr = '거래취소';
                } else {
                    $paging->data[$key]->status_kr = '거래완료';
                }
            }

            return view('trade.information', ['data' => $paging, 'pagination' => $pagination]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function action(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tradeNo' => ['required', 'integer']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            DB::beginTransaction();
            $flag = false;
            $tradeLog = DB::table('tr_trade_log')->where('no', $validated['tradeNo'])->lockForUpdate()->first();
            if($tradeLog->seller_no === Auth::user()->no) {
                // 판매
                $params = ['seller_trade_status' => 't', 'seller_status_date' => date('Y-m-d H:i:s'), 'update_date' => date('Y-m-d H:i:s')];
                if($tradeLog->buyer_trade_status === 't') {
                    $params = array_merge($params, ['status' => 't', 'trade_success_date' => date('Y-m-d H:i:s')]);
                    $flag = true;
                }
            } else {
                // 구매
                $params = ['buyer_trade_status' => 't', 'buyer_status_date' => date('Y-m-d H:i:s'), 'update_date' => date('Y-m-d H:i:s')];
                if($tradeLog->seller_trade_status === 't') {
                    $params = array_merge($params, ['status' => 't', 'trade_success_date' => date('Y-m-d H:i:s')]);
                    $flag = true;
                }
            }

            $updateLog = DB::table('tr_trade_log')->where('no', $validated['tradeNo'])->update($params);
            if(!$updateLog) {
                $validator->errors()->add('tradeNo', '로그 저장에 실패했습니다.');
                throw new DatabaseException();
            }

            if(!$flag) {
                DB::commit();
                return redirect('/trade/list');
                die();
            }

            // 판매자 마일리지 로직
            $sellerModel = DB::table('tr_mileage')->where('user_no', $tradeLog->seller_no)->lockForUpdate()->first();
            $commissionMileage = $tradeLog->trade_price * 0.05;
            $sellerCalcMileage = ceil($tradeLog->trade_price - $commissionMileage);

            $sellerUpdateLog = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => $sellerModel->user_no,
                'method' => 'trade',
                'method_no' => $tradeLog->no,
                'before_mileage' => $sellerModel->use_mileage,
                'use_mileage' => $sellerCalcMileage,
                'after_mileage' => $sellerModel->use_mileage + $sellerCalcMileage
            ]);
            if(!$sellerUpdateLog) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $sellerMileageUpdateRow = DB::table('tr_mileage')->where('user_no', $sellerModel->user_no)->update([
                'use_mileage' => $sellerModel->use_mileage + $sellerCalcMileage,
                'real_mileage' => $sellerModel->real_mileage + $sellerCalcMileage,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$sellerMileageUpdateRow) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            // 구매자 작업
            $buyerModel = DB::table('tr_mileage')->where('user_no', $tradeLog->buyer_no)->lockForUpdate()->first();

            $buyerUpdateRow = DB::table('tr_mileage')->where('user_no', $tradeLog->buyer_no)->update([
                'using_mileage' => $buyerModel->using_mileage - $tradeLog->trade_price,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$buyerUpdateRow) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/trade/list');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator);
        }
    }

    public function delete(Request $request, $no)
    {
        try {
            $validator = Validator::make($request->all(), [
                'boardNo' => ['required', 'integer']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            if($no !== $validated['boardNo']) {
                $validator->errors()->add('boardNo', '데이터가 변경 되었습니다.');
                throw new Exception();
            }

            DB::beginTransaction();

            $boardModel = DB::table('tr_trade_board')->where('no', $validated['boardNo'])->where('status', 't')->lockForUpdate()->first();
            if(!$boardModel) {
                $validator->errors()->add('boardNo', '해당 게시글이 존재하지 않습니다.');
                throw new DatabaseException();
            }
            if($boardModel->user_no !== Auth::user()->no) {
                $validator->errors()->add('boardNo', '해당 게시글에 대한 권한이 없습니다.');
                throw new DatabaseException();
            }

            $boardUpdateRow = DB::table('tr_trade_board')->where('no', $validated['boardNo'])->update(['status' => 'f', 'update_date' => date('Y-m-d H:i:s')]);
            if(!$boardUpdateRow) {
                $validator->errors()->add('boardNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $imageModels = DB::table('tr_image')->where('method', 'trade')->where('reference_no', $validated['boardNo'])->lockForUpdate()->get();
            if(!$imageModels) {
                $validator->errors()->add('boardNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $imageUpdateRow = DB::table('tr_image')->where('method', 'trade')->where('reference_no', $validated['boardNo'])->update(['status' => 'f', 'update_date' => date('Y-m-d H:i:s')]);
            if(!$imageUpdateRow) {
                $validator->errors()->add('boardNo', '이미지 삭제에 실패하였습니다.');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/trade');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator);
        }
    }

}
