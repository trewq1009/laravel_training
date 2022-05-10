@include('layout.head')
@include('layout.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/trade/list');?>' method="post" id="formAction">
        @csrf
        @if ($errors->has('tradeNo'))
            <span class="text-danger">{{ $errors->first('tradeNo') }}</span>
        @endif
        <input type="hidden" id="tradeNo" name="tradeNo" value="">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">TRADE 종류</th>
                <th scope="col">상품명</th>
                <th scope="col">가격</th>
                <th scope="col">상대상태</th>
                <th scope="col">나의거래상태</th>
                <th scope="col">최종거래상태</th>
                <th scope="col">#</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data->data as $item)
            <tr>
                <td>{{$item->trade_name}}</td>
                <td>{{$item->product_name}}</td>
                <td>{{$item->trade_price}}</td>
                <td>{{$item->user_status}}</td>
                <td>{{$item->me_status}}</td>
                <td>{{$item->status_kr}}</td>
                <td>
                    @if($item->status === 'a')
                    <button type="button" onclick="tradeSuccess(this)" value="{{$item->no}}" data-trade="{{$item->trade_name}}" name="tradeNo" class="btn btn-outline-info">눌러서 완료하기</button>
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
        document.querySelector('#formAction').submit();
    }
</script>

@include('layout.footer')
