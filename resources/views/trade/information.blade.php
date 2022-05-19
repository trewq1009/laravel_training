@include('layout.head')
@include('layout.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/trade/list/success');?>' method="post" id="formAction">
        @csrf
        @if ($errors->has('tradeNo'))
            <span class="text-danger">{{ $errors->first('tradeNo') }}</span>
        @endif
        <input type="hidden" id="tradeNo" name="tradeNo" value="">
        <input type="hidden" id="tradeName" name="tradeName" value="">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">TRADE 종류</th>
                <th scope="col">상품명</th>
                <th scope="col">가격</th>
                <th scope="col">상대 상태</th>
                <th scope="col">본인 상태</th>
                <th scope="col">#</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data->data as $item)
            <tr>
                <td>{{$item->trade_name}}</td>
                <td>{{$item->product_name}}</td>
                <td>{{$item->trade_price}}</td>
                <td>{{$item->other_status_kr}}</td>
                <td>{{$item->user_status_kr}}</td>
                <td>
                    @if($item->user_status === 'a' && $item->other_status !== 'f')
                    <button type="button" onclick="tradeSuccess(this)" value="{{$item->no}}" data-trade="{{$item->trade_name}}" name="tradeNo" class="btn btn-outline-info">눌러서 거래확정</button>
                    @endif
                    @if($item->user_status === 'a' && $item->other_status !== 'f')
                    <button type="button" onclick="tradeCancel(this)" value="{{$item->no}}" data-trade="{{$item->trade_name}}" name="tradeNo" class="btn btn-outline-info">거래 취소</button>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </form>
    <div>
        {{$pagination->links()}}
    </div>
</section>
<script>
    function tradeSuccess(event) {
        const tradeName = event.dataset.trade;
        const resultConfirm = window.confirm(tradeName + '를 완료 하시겠습니까?');
        if(!resultConfirm) return;
        document.querySelector('#tradeNo').value = event.value;
        document.querySelector('#tradeName').value = event.dataset.trade;
        document.querySelector('#formAction').submit();
    }

    function tradeCancel(event) {
        if(!window.confirm('거래를 취소하시겠습니까?')) return;
        document.querySelector('#tradeNo').value = event.value;
        document.querySelector('#tradeName').value = event.dataset.trade;
        document.querySelector('#formAction').action = '/trade/list/cancel';
        document.querySelector('#formAction').submit();
    }
</script>

@include('layout.footer')
