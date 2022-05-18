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

    protected array $tradeStatus = ['a1'=>'거래 신청 완료', 'a2'=>'거래 확정 요청', 't1'=>'거래완료',
                                    'f1'=>'판매자 거래 취소', 'f2'=>'구매자 거래 취소'];


    public function list()
    {
        try {
            return view('trade.list', [
                'data' => DB::table('tr_product')->where('status', 't')->orderByDesc('no')->paginate(10),
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
                'productAmount' => ['required', 'integer', 'min:1', 'max:99'],
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

            $productNo = DB::table('tr_product')->insertGetId([
                'user_no' =>Auth::user()->no,
                'product_name' => $validated['productName'],
                'product_information' => $validated['productInformation'],
                'price' => $validated['productPrice'],
                'amount' => $validated['productAmount'],
                'status' => 't'
            ]);
            if(!$productNo) {
                throw new DatabaseException();
            }

            $imageNo = DB::table('tr_image')->insertGetId([
                'method' => 'product',
                'reference_no' => $productNo,
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
            $boardModel = DB::table('tr_product')->where('status', 't')->where('no', $no)->first();
            $imageModel = DB::table('tr_image')->where('status', 't')
                ->where('method', 'product')->where('reference_no', $no)->first();

            return view('trade.detail', ['board' => $boardModel, 'image' => $imageModel, 'auth' => Auth::user()]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function trading(Request $request, $no)
    {
        try {
            $validator = Validator::make($request->all(), [
                'boardNo' => ['required', 'integer'], 'tradeAmount' => ['required', 'integer', 'min:1']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            if($no !== $validated['boardNo']) {
                $validator->errors()->add('boardNo', '잘못된 접근 입니다.');
                throw new Exception();
            }

            DB::beginTransaction();

            $productModel = DB::table('tr_product')->where('status', 't')
                ->where('no', $validated['boardNo'])->lockForUpdate()->first();

            if($productModel->user_no === Auth::user()->no) {
                $validator->errors()->add('boardNo', '본인글에 신청 하셨습니다.');
                throw new DatabaseException();
            }
            if($productModel->amount <= 0) {
                $validator->errors()->add('boardNo', '상품 갯수가 부족합니다.');
                throw new DatabaseException();
            }
            if($productModel->amount < $validated['tradeAmount']) {
                $validator->errors()->add('tradeAmount', '구매 가능 갯수를 넘었습니다.');
                throw new DatabaseException();
            }

            $buyerMileageModel = DB::table('tr_mileage')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();

            // 결재할 총 금액
            $totalPrice = $productModel->price * $validated['tradeAmount'];

            $totalMileage = $buyerMileageModel->event_mileage + $buyerMileageModel->real_mileage;

            if($totalPrice > $totalMileage) {
                $validator->errors()->add('boardNo', '마일리지가 부족합니다.');
                throw new DatabaseException();
            }

            // 거래 테이블 생성
            $tradeNo = DB::table('tr_trade')->insertGetId([
                'trade_board_no' => $validated['boardNo'],
                'seller_no' => $productModel->user_no,
                'buyer_no' => Auth::user()->no,
                'trade_price' => $totalPrice,
                'trade_amount' => $validated['tradeAmount']
            ]);
            if(!$tradeNo) {
                $validator->errors()->add('boardNo', '거래 신청에 실패했습니다.');
                throw new DatabaseException();
            }

            // 계산 영역 이벤트 마일리지부터
            if($buyerMileageModel->event_mileage > 0) {
                $newPrice = $buyerMileageModel->event_mileage - $totalPrice;

                if($newPrice < 0) {
                    $eventMileageLog = DB::table('tr_mileage_log')->insertGetId([
                        'user_no' => Auth::user()->no, 'method' => 'trade',
                        'method_no' => $tradeNo, 'using_plus' => $buyerMileageModel->event_mileage,
                        'event_minus' => $buyerMileageModel->event_mileage
                    ]);
                    if(!$eventMileageLog) {
                        $validator->errors()->add('boardNo', '이벤트 마일리지 관련 에러 발생');
                        throw new DatabaseException();
                    }

                    $realMileageLog = DB::table('tr_mileage_log')->insertGetId([
                        'user_no' => Auth::user()->no, 'method' => 'trade', 'method_no' => $tradeNo,
                        'using_plus' => abs($newPrice), 'real_minus' => abs($newPrice)
                    ]);
                    if(!$realMileageLog) {
                        $validator->errors()->add('boardNo', '마일리지 관련 에러 발생');
                        throw new DatabaseException();
                    }

                    $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                        'using_mileage' => $buyerMileageModel->using_mileage + $totalPrice,
                        'event_mileage' => 0, 'real_mileage' => $buyerMileageModel->real_mileage - $newPrice,
                        'update_date' => date('Y-m-d H:i:s')
                    ]);
                    if(!$mileageUpdateRow) {
                        $validator->errors()->add('boardNo', '마일리지 사용 에러 발생');
                        throw new DatabaseException();
                    }

                } else {
                    $eventMileageLog = DB::table('tr_mileage_log')->insertGetId([
                        'user_no' => Auth::user()->no, 'method' => 'trade',
                        'method_no' => $tradeNo, 'using_plus' => $totalPrice,
                        'event_minus' => $totalPrice
                    ]);
                    if(!$eventMileageLog) {
                        $validator->errors()->add('boardNo', '이벤트 마일리지 관련 에러 발생');
                        throw new DatabaseException();
                    }
                    $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                        'using_mileage' => $buyerMileageModel->using_mileage + $totalPrice,
                        'event_mileage' => abs($newPrice), 'update_date' => date('Y-m-d H:i:s')
                    ]);
                    if(!$mileageUpdateRow) {
                        $validator->errors()->add('boardNo', '마일리지 사용 에러 발생');
                        throw new DatabaseException();
                    }
                }
            } else {
                $realMileageLog = DB::table('tr_mileage_log')->insertGetId([
                    'user_no' => Auth::user()->no, 'method' => 'trade', 'method_no' => $tradeNo,
                    'using_plus' => $totalPrice, 'real_minus' => $totalPrice
                ]);
                if(!$realMileageLog) {
                    $validator->errors()->add('boardNo', '마일리지 관련 에러 발생');
                    throw new DatabaseException();
                }
                $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                    'using_mileage' => $buyerMileageModel->using_mileage + $totalPrice,
                    'real_mileage' => $buyerMileageModel->real_mileage - $totalPrice, 'update_date' => date('Y-m-d H:i:s')
                ]);
                if(!$mileageUpdateRow) {
                    $validator->errors()->add('boardNo', '마일리지 사용 에러 발생');
                    throw new DatabaseException();
                }
            }

            if($productModel->amount - $validated['tradeAmount'] === 0) {
                $productUpdateParams = [
                    'amount' => $productModel->amount - $validated['tradeAmount'],
                    'update_date' => date('Y-m-d H:i:s'),
                    'status' => 'c'
                ];
            } else {
                $productUpdateParams = [
                    'amount' => $productModel->amount - $validated['tradeAmount'],
                    'update_date' => date('Y-m-d H:i:s')
                ];
            }
            $productUpdateRow = DB::table('tr_product')->where('status', 't')
                ->where('no', $validated['boardNo'])->update($productUpdateParams);
            if(!$productUpdateRow) {
                $validator->errors()->add('boardNo', '거래 신청에 실패하였습니다.');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/trade/list/buy');

        } catch (DatabaseException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($validator);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($validator);
        }
    }

    public function tradeList($method)
    {
        try {

            if($method === 'buy') {
                $where = 'buyer_no';
                $tradeName = '구매';
            } else {
                $where = 'seller_no';
                $tradeName = '판매';
            }

            $pagination = DB::table('tr_trade')->where($where, Auth::user()->no)
                ->orderByDesc('no')->paginate(10);
            $paging = (object)$pagination;
            $paging = json_encode($paging);
            $paging = json_decode($paging);

            foreach ($paging->data as $key => $value) {
                $paging->data[$key]->trade_name = $tradeName;

                $productModel = DB::table('tr_product')->where('no', $value->trade_board_no)->first();
                $paging->data[$key]->product_name = $productModel->product_name;

                $paging->data[$key]->status_kr = $this->tradeStatus[$value->status];
            }

            return view('trade.information', ['data' => $paging, 'pagination' => $pagination]);

        } catch (Exception $e) {
            return redirect()->back();
        }
    }

    public function cancel(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),[
                'tradeNo' => ['required', 'integer'],
                'tradeName' => ['required', 'alpha']
            ]);
            if($validator->fails()) {
                $validator->errors()->add('tradeNo', '잘못된 경로 입니다.');
                throw new Exception();
            }
            $validated = $validator->validated();

            DB::beginTransaction();

            $tradeModel = DB::table('tr_trade')->where('no', $validated['tradeNo'])->lockForUpdate()->first();
            if(!$tradeModel) {
                $validator->errors()->add('tradeNo', '잘못된 거래글 입니다.');
                throw new DatabaseException();
            }

            if($validated['tradeName'] === '구매') {
                $params = ['status' => 'f2', 'cancel_date' => date('Y-m-d H:i:s'),
                    'buyer_status_date' => date('Y-m-d H:i:s'), 'update_date' => date('Y-m-d H:i:s')];
            } else {
                $params = ['status' => 'f1', 'cancel_date' => date('Y-m-d H:i:s'),
                    'seller_status_date' => date('Y-m-d H:i:s'), 'update_date' => date('Y-m-d H:i:s')];
            }

            $tradeUpdateRow = DB::table('tr_trade')->where('no', $validated['tradeNo'])->update($params);
            if(!$tradeUpdateRow) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $productModel = DB::table('tr_product')
                ->where('no', $tradeModel->trade_board_no)->lockForUpdate()->first();
            if(!$productModel) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }
            if($productModel->status === 'f') {
                $validator->errors()->add('tradeNo', '상품 상태값 에러');
                throw new DatabaseException();
            }

            $calcAmount = $productModel->amount + $tradeModel->trade_amount;

            $productUpdateRow = DB::table('tr_product')->where('no', $tradeModel->trade_board_no)->update([
                'amount' => $calcAmount, 'status' => 't', 'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$productUpdateRow) {
                $validator->errors()->add('tradeNo', '상품 재설정에 실패 했습니다.');
                throw new DatabaseException();
            }

            $buyerMileageModel = DB::table('tr_mileage')->where('user_no', $tradeModel->buyer_no)->lockForUpdate()->first();
            if(!$buyerMileageModel) {
                throw new DatabaseException();
            }
            if($buyerMileageModel->using_mileage < $tradeModel->trade_price) {
                $validator->errors()->add('tradeNo', '마일리지 무결성 에러');
                throw new DatabaseException();
            }

            $buyerMileageLogModel = DB::table('tr_mileage_log')->where('user_no', $tradeModel->buyer_no)
                ->where('method', 'trade')->where('method_no', $tradeModel->trade_board_no)->get();
            if(!$buyerMileageLogModel) {
                $validator->errors()->add('tradeNo', '로그 에러');
                throw new DatabaseException();
            }

            [$usingMileage, $eventMileage, $realMileage, $prevMileageLogNo] = 0;
            foreach ($buyerMileageLogModel as $item) {
                $usingMileage += $item->using_plus;
                $eventMileage += $item->event_minus;
                $realMileage += $item->real_minus;
                $prevMileageLogNo = $item->no;
            }

            $buyerMileageLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => $tradeModel->buyer_no, 'method' => 'cancel', 'method_no' => $prevMileageLogNo,
                'using_minus' => $usingMileage, 'event_plus' => $eventMileage,
                'real_plus' => $realMileage
            ]);
            if(!$buyerMileageLogNo) {
                $validator->errors()->add('tradeNo', '로그 저장 에러');
                throw new DatabaseException();
            }

            $mileageUpdateRow = DB::table('tr_mileage')->where('user_no', $tradeModel->buyer_no)->update([
                'using_mileage' => $buyerMileageModel->using_mileage - $usingMileage,
                'event_mileage' => $buyerMileageModel->event_mileage +$eventMileage,
                'real_mileage' => $buyerMileageModel->real_mileage + $realMileage,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$mileageUpdateRow) {
                $validator->errors()->add('tradeNo', '마일리지 변동 에러');
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
                $params = [
                    'seller_trade_status' => 't', 'seller_status_date' => date('Y-m-d H:i:s'),
                    'update_date' => date('Y-m-d H:i:s')
                ];
                if($tradeLog->buyer_trade_status === 't') {
                    $params = array_merge($params, ['status' => 't', 'trade_success_date' => date('Y-m-d H:i:s')]);
                    $flag = true;
                }
            } else {
                // 구매
                $params = [
                    'buyer_trade_status' => 't', 'buyer_status_date' => date('Y-m-d H:i:s'),
                    'update_date' => date('Y-m-d H:i:s')
                ];
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

            $boardModel = DB::table('tr_trade_board')->where('no', $validated['boardNo'])
                ->where('status', 't')->lockForUpdate()->first();
            if(!$boardModel) {
                $validator->errors()->add('boardNo', '해당 게시글이 존재하지 않습니다.');
                throw new DatabaseException();
            }
            if($boardModel->user_no !== Auth::user()->no) {
                $validator->errors()->add('boardNo', '해당 게시글에 대한 권한이 없습니다.');
                throw new DatabaseException();
            }

            $boardUpdateRow = DB::table('tr_trade_board')->where('no', $validated['boardNo'])
                ->update(['status' => 'f', 'update_date' => date('Y-m-d H:i:s')]);
            if(!$boardUpdateRow) {
                $validator->errors()->add('boardNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $imageModels = DB::table('tr_image')->where('method', 'trade')
                ->where('reference_no', $validated['boardNo'])->lockForUpdate()->get();
            if(!$imageModels) {
                $validator->errors()->add('boardNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $imageUpdateRow = DB::table('tr_image')->where('method', 'trade')
                ->where('reference_no', $validated['boardNo'])
                ->update(['status' => 'f', 'update_date' => date('Y-m-d H:i:s')]);
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
