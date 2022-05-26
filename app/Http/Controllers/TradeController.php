<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Exceptions\DatabaseException;

const STATUS_TRUE = 't';
const STATUS_AWAIT = 'a';
const STATUS_FALSE = 'f';
const STATUS_CLEAR = 'c';

class TradeController extends Controller
{
    protected array $tradeStatus = ['a'=>'거래중', 'f'=>'거래취소', 't'=>'거래완료'];

    public function list()
    {
        try {
            return view('trade.list', [
                'data' => DB::table('tr_product')->where('status', STATUS_TRUE)->orderByDesc('no')->paginate(10),
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
                'productAmount' => ['required', 'integer', 'min:1', 'max:9999'],
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
                'status' => STATUS_TRUE
            ]);
            if(!$productNo) {
                throw new DatabaseException();
            }

            $imageNo = DB::table('tr_image')->insertGetId([
                'method' => 'product',
                'reference_no' => $productNo,
                'image_name' => $newFileName,
                'status' => STATUS_TRUE
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
            $boardModel = DB::table('tr_product')->where('status', STATUS_TRUE)->where('no', $no)->first();
            $imageModel = DB::table('tr_image')->where('status', STATUS_TRUE)
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
                'boardNo' => ['required', 'integer'],
                'tradeAmount' => ['required', 'integer', 'min:1']
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

            $productModel = DB::table('tr_product')->where('status', STATUS_TRUE)
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
            $totalPrice = $productModel->price * $validated['tradeAmount'];;

            if($totalPrice > $buyerMileageModel->mileage) {
                $validator->errors()->add('boardNo', '마일리지가 부족합니다.');
                throw new DatabaseException();
            }

            // 거래 테이블 생성
            $tradeNo = DB::table('tr_trade')->insertGetId([
                'product_no' => $validated['boardNo'],
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
            $userDetailMileageModel = DB::table('tr_mileage_detail')
                ->where('user_no', Auth::user()->no)->lockForUpdate()->first();
            if(!$userDetailMileageModel) {
                $validator->errors()->add('boardNo', '마일리지 에러');
                throw new DatabaseException();
            }

            if($userDetailMileageModel->event_mileage > 0) {
                $newPrice = $userDetailMileageModel->event_mileage - $totalPrice;
                if($newPrice < 0) {
                    $updateParams = [
                        'event_mileage' => 0, 'real_mileage' => $userDetailMileageModel->real_mileage - abs($newPrice),
                        'update_date' => date('Y-m-d H:i:s')
                    ];
                    $mileageLogParams = [
                        'user_no' => Auth::user()->no, 'method' => 'trade', 'method_no' => $tradeNo,
                        'before_mileage' => $buyerMileageModel->mileage,
                        'use_mileage' => $totalPrice,
                        'after_mileage' => $buyerMileageModel->mileage - $totalPrice,
                        'real_mileage_usage' => abs($newPrice),
                        'event_mileage_usage' => $userDetailMileageModel->event_mileage
                    ];
                } else {
                    $updateParams = ['event_mileage' => $newPrice, 'update_date' => date('Y-m-d H:i:s')];
                    $mileageLogParams = [
                        'user_no' => Auth::user()->no, 'method' => 'trade', 'method_no' => $tradeNo,
                        'before_mileage' => $buyerMileageModel->mileage,
                        'use_mileage' => $totalPrice,
                        'after_mileage' => $buyerMileageModel->mileage - $totalPrice,
                        'event_mileage_usage' => $totalPrice
                    ];
                }

            } else {
                $updateParams = ['real_mileage' => $userDetailMileageModel->real_mileage - $totalPrice,
                    'update_date' => date('Y-m-d H:i:s')];
                $mileageLogParams = [
                    'user_no' => Auth::user()->no, 'method' => 'trade', 'method_no' => $tradeNo,
                    'before_mileage' => $buyerMileageModel->mileage,
                    'use_mileage' => $totalPrice,
                    'after_mileage' => $buyerMileageModel->mileage - $totalPrice,
                    'real_mileage_usage' => $totalPrice
                ];
            }

            $userDetailUpdateRow = DB::table('tr_mileage_detail')
                ->where('user_no', Auth::user()->no)->update($updateParams);
            if(!$userDetailUpdateRow) {
                $validator->errors()->add('boardNo', '마일리지 사용 에러');
                throw new DatabaseException();
            }

            $buyerMileageUpdateRow = DB::table('tr_mileage')->where('user_no', Auth::user()->no)->update([
                'mileage' => $buyerMileageModel->mileage - $totalPrice,
                'using_mileage' => $buyerMileageModel->using_mileage + $totalPrice,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$buyerMileageUpdateRow) {
                $validator->errors()->add('boardNo', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $mileageLogRow = DB::table('tr_mileage_log')->insertGetId($mileageLogParams);
            if(!$mileageLogRow) {
                $validator->errors()->add('boardNo', '마일리지 로그 에러');
                throw new DatabaseException();
            }

            if($productModel->amount - $validated['tradeAmount'] === 0) {
                $productUpdateParams = [
                    'amount' => $productModel->amount - $validated['tradeAmount'],
                    'update_date' => date('Y-m-d H:i:s'),
                    'sales_date' => date('Y-m-d H:i:s'),
                    'status' => STATUS_CLEAR
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

                $productModel = DB::table('tr_product')->where('no', $value->product_no)->first();
                $paging->data[$key]->product_name = $productModel->product_name;

                if($method === 'buy') {
                    $paging->data[$key]->other_status_kr = $this->tradeStatus[$value->seller_status];
                    $paging->data[$key]->user_status_kr = $this->tradeStatus[$value->buyer_status];
                    $paging->data[$key]->other_status = $value->seller_status;
                    $paging->data[$key]->user_status = $value->buyer_status;
                } else {
                    $paging->data[$key]->other_status_kr = $this->tradeStatus[$value->buyer_status];
                    $paging->data[$key]->user_status_kr = $this->tradeStatus[$value->seller_status];
                    $paging->data[$key]->other_status = $value->buyer_status;
                    $paging->data[$key]->user_status = $value->seller_status;
                }
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
                $params = [
                    'buyer_status' => STATUS_FALSE,
                    'cancel_date' => date('Y-m-d H:i:s'),
                    'buyer_status_date' => date('Y-m-d H:i:s'),
                    'update_date' => date('Y-m-d H:i:s'),
                    'status' => STATUS_FALSE
                ];
            } else {
                $params = [
                    'seller_status' => STATUS_FALSE,
                    'cancel_date' => date('Y-m-d H:i:s'),
                    'seller_status_date' => date('Y-m-d H:i:s'),
                    'update_date' => date('Y-m-d H:i:s'),
                    'status' => STATUS_FALSE
                ];
            }

            $tradeUpdateRow = DB::table('tr_trade')->where('no', $validated['tradeNo'])->update($params);
            if(!$tradeUpdateRow) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }

            $productModel = DB::table('tr_product')
                ->where('no', $tradeModel->product_no)->lockForUpdate()->first();
            if(!$productModel) {
                $validator->errors()->add('tradeNo', '작업에 실패하였습니다.');
                throw new DatabaseException();
            }
            if($productModel->status === STATUS_FALSE) {
                $validator->errors()->add('tradeNo', '상품 상태값 에러');
                throw new DatabaseException();
            }

            $calcAmount = $productModel->amount + $tradeModel->trade_amount;

            $productUpdateRow = DB::table('tr_product')->where('no', $tradeModel->product_no)->update([
                'amount' => $calcAmount,
                'status' => STATUS_TRUE,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$productUpdateRow) {
                $validator->errors()->add('tradeNo', '상품 재설정에 실패 했습니다.');
                throw new DatabaseException();
            }

            $buyerMileageModel = DB::table('tr_mileage')
                ->where('user_no', $tradeModel->buyer_no)->lockForUpdate()->first();
            if(!$buyerMileageModel) {
                $validator->errors()->add('tradeNo', '마일리지 로드 에러');
                throw new DatabaseException();
            }
            if($buyerMileageModel->using_mileage < $tradeModel->trade_price) {
                $validator->errors()->add('tradeNo', '마일리지 무결성 에러');
                throw new DatabaseException();
            }

            $buyerLogModel = DB::table('tr_mileage_log')->where('user_no', $tradeModel->buyer_no)
                ->where('method', 'trade')->where('method_no', $validated['tradeNo'])->first();
            if(!$buyerLogModel) {
                $validator->errors()->add('tradeNo', '로그 불러오기 에러');
                throw new DatabaseException();
            }

            $buyerDetailModel = DB::table('tr_mileage_detail')->where('user_no', $tradeModel->buyer_no)
                ->lockForUpdate()->first();
            if(!$buyerDetailModel) {
                $validator->errors()->add('tradeNo', '마일리지 로드 에러');
                throw new DatabaseException();
            }

            $buyerDetailUpdateRow = DB::table('tr_mileage_detail')->where('user_no', $tradeModel->buyer_no)->update([
                'real_mileage' => $buyerDetailModel->real_mileage + $buyerLogModel->real_mileage_usage,
                'event_mileage' => $buyerDetailModel->event_mileage + $buyerLogModel->event_mileage_usage,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$buyerDetailUpdateRow) {
                $validator->errors()->add('tradeNo', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $totalPrevMileage = $buyerLogModel->real_mileage_usage + $buyerLogModel->event_mileage_usage;
            $buyerMileageUpdateRow = DB::table('tr_mileage')->where('user_no', $tradeModel->buyer_no)->update([
                'mileage' => $buyerMileageModel->mileage + $totalPrevMileage,
                'using_mileage' => $buyerMileageModel->using_mileage - $totalPrevMileage,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$buyerMileageUpdateRow) {
                $validator->errors()->add('tradeNo', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $buyerLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => $tradeModel->buyer_no, 'method' => 'cancel', 'method_no' => $buyerLogModel->no,
                'before_mileage' => $buyerMileageModel->mileage,
                'use_mileage' => $totalPrevMileage,
                'after_mileage' => $buyerMileageModel->mileage + $totalPrevMileage,
                'real_mileage_usage' => $buyerLogModel->real_mileage_usage,
                'event_mileage_usage' => $buyerLogModel->event_mileage_usage
            ]);
            if(!$buyerLogNo) {
                $validator->errors()->add('tradeNo', '마일리지 로그 에러');
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

    public function success(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tradeNo' => ['required', 'integer'],
                'tradeName' => ['required', 'alpha']
            ]);
            if($validator->fails()) {
                throw new Exception();
            }
            $validated = $validator->validated();

            DB::beginTransaction();

            $tradeModel = DB::table('tr_trade')->where('no', $validated['tradeNo'])->lockForUpdate()->first();
            if(!$tradeModel) {
                $validator->errors()->add('tradeNo', '잘못된 게시글 입니다.');
                throw new DatabaseException();
            }
            if($tradeModel->seller_status === STATUS_FALSE ||
                $tradeModel->buyer_status === STATUS_FALSE ||
                $tradeModel->status === STATUS_FALSE) {
                $validator->errors()->add('tradeNo', '상태값 에러');
                throw new DatabaseException();
            }

            $params = [];
            $flag = false;
            if($validated['tradeName'] === '구매') {
                $params += [
                    'buyer_status' => STATUS_TRUE,
                    'buyer_status_date' => date('Y-m-d H:i:s'),
                    'update_date' => date('Y-m-d H:i:s')
                ];
                if($tradeModel->seller_status === STATUS_TRUE) {
                    $params += [
                        'success_date' => date('Y-m-d H:i:s'),
                        'status' => STATUS_TRUE
                    ];
                    $flag = true;
                }

            } else {
                $params += [
                    'seller_status' => STATUS_TRUE,
                    'seller_status_date' => date('Y-m-d H:i:s'),
                    'update_date' => date('Y-m-d H:i:s')
                ];
                if($tradeModel->buyer_status === STATUS_TRUE) {
                    $params += [
                        'success_date' => date('Y-m-d H:i:s'),
                        'status' => STATUS_TRUE
                    ];
                    $flag = true;
                }
            }

            $tradeUpdateRow = DB::table('tr_trade')->where('no', $tradeModel->no)->update($params);
            if(!$tradeUpdateRow) {
                $validator->errors()->add('tradeNo', '거래상태 업데이트 실패');
                throw new DatabaseException();
            }

            if(!$flag) {
                DB::commit();
                if($validated['tradeName'] === '구매') {
                    return redirect('/trade/list/buy');
                } else {
                    return redirect('/trade/list/sell');
                }
                die();
            }

            // 거래 완료 시점
            // 구매자 마일리지 작업
            $buyerMileageModel = DB::table('tr_mileage')
                ->where('user_no', $tradeModel->buyer_no)->lockForUpdate()->first();
            if(!$buyerMileageModel) {
                $validator->errors()->add('tradeNo', '마일리지 로드 에러');
                throw new DatabaseException();
            }
            if($buyerMileageModel->using_mileage < $tradeModel->trade_price) {
                $validator->errors()->add('tradeNo', '마일리지 무결성 에러');
                throw new DatabaseException();
            }

            $buyerMileageUpdateRow = DB::table('tr_mileage')->where('user_no', $tradeModel->buyer_no)->update([
                'using_mileage' => $buyerMileageModel->using_mileage - $tradeModel->trade_price,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$buyerMileageUpdateRow) {
                $validator->errors()->add('tradeNo', '마일리지 작업 에러');
                throw new DatabaseException();
            }

            // 판매자 마일리지 작업
            $sellerDetailModel = DB::table('tr_mileage_detail')
            ->where('user_no', $tradeModel->seller_no)->lockForUpdate()->first();
            if(!$sellerDetailModel) {
                $validator->errors()->add('tradeNo', '마일리지 불러오기 에러');
                throw new DatabaseException();
            }

            // commission
            $commission = $tradeModel->trade_price * 0.05;
            $newPrice = ceil($tradeModel->trade_price - $commission);

            $sellerNewMileage = $sellerDetailModel->real_mileage + $newPrice;
            $sellerDetailUpdateRow = DB::table('tr_mileage_detail')
                ->where('user_no', $tradeModel->seller_no)->update([
                'real_mileage' => $sellerNewMileage,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$sellerDetailUpdateRow) {
                $validator->errors()->add('tradeNo', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $sellerMileageModel = DB::table('tr_mileage')
            ->where('user_no', $tradeModel->seller_no)->lockForUpdate()->first();
            if(!$sellerMileageModel) {
                $validator->errors()->add('tradeNo', '마일리지 로드 에러');
                throw new DatabaseException();
            }
            if($sellerMileageModel->mileage !== $sellerDetailModel->real_mileage + $sellerDetailModel->event_mileage) {
                $validator->errors()->add('tradeNo', '마일리지 무결성 에러');
                throw new DatabaseException();
            }

            $sellerMileageUpdateRow = DB::table('tr_mileage')->where('user_no', $tradeModel->seller_no)->update([
                'mileage' => $sellerMileageModel->mileage + $newPrice,
                'update_date' => date('Y-m-d H:i:s')
            ]);
            if(!$sellerMileageUpdateRow) {
                $validator->errors()->add('tradeNo', '마일리지 변동 에러');
                throw new DatabaseException();
            }

            $sellerLogNo = DB::table('tr_mileage_log')->insertGetId([
                'user_no' => $tradeModel->seller_no, 'method' => 'trade', 'method_no' => $tradeModel->no,
                'before_mileage' => $sellerMileageModel->mileage,
                'use_mileage' => $newPrice,
                'after_mileage' => $sellerMileageModel->mileage + $newPrice
            ]);
            if(!$sellerLogNo) {
                $validator->errors()->add('tradeNo', '마일리지 로그 에러');
                throw new DatabaseException();
            }

            DB::commit();
            return redirect('/trade/list/sell');

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

            $boardModel = DB::table('tr_product')->where('no', $validated['boardNo'])
                ->where('status', STATUS_TRUE)->lockForUpdate()->first();
            if(!$boardModel) {
                $validator->errors()->add('boardNo', '상품이 존재하지 않습니다.');
                throw new DatabaseException();
            }
            if($boardModel->user_no !== Auth::user()->no) {
                $validator->errors()->add('boardNo', '해당 상품에 대한 권한이 없습니다.');
                throw new DatabaseException();
            }

            $tradeListModel = DB::table('tr_trade')->where('product_no', $boardModel->no)
                ->where('status', STATUS_AWAIT)->first();
            if($tradeListModel) {
                $validator->errors()->add('boardNo', '현재 거래중인 내역이 있습니다.');
                throw new DatabaseException();
            }

            $boardUpdateRow = DB::table('tr_product')->where('no', $validated['boardNo'])
                ->update(['status' => STATUS_FALSE, 'update_date' => date('Y-m-d H:i:s')]);
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
                ->update(['status' => STATUS_FALSE, 'update_date' => date('Y-m-d H:i:s')]);
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
