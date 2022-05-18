@include('layout.head')
@include('layout.header')

<section class="container">
    <div style="margin: 1rem 0">
        <a class="btn btn-outline-primary" href="/trade/registration">거래등록</a>
        <a class="btn btn-outline-info" href="/trade/list/buy">구매내역</a>
        <a class="btn btn-outline-info" href="/trade/list/sell">판매내역</a>
    </div>

    <div class="list-group">
        @if($data)
            @foreach($data as $item)
            <a href="/trade/detail/{{$item->no}}" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">{{$item->product_name}}</h5>
                    <span>{{$item->price}} 원</span>
                </div>
                <div class="d-flex w-100 justify-content-between">
                    <small>{{$item->registration_date}}</small>
                    @if($item->user_no === $auth->no)
                    <span>본인글</span>
                    @endif
                </div>
            </a>
            @endforeach
        @else
        <a href="#" class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">거래중인 유저가 없습니다.</h5>
            </div>
        </a>
        @endif
    </div>
    <div>
        {{$data->links()}}
    </div>

</section>

@include('layout.footer')
