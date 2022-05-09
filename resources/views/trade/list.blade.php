@include('layout.head')
@include('layout.header')

<section class="container">
    <div style="margin: 1rem 0">
        <a class="btn btn-outline-primary" href="/trade/registration">거래등록</a>
        <a class="btn btn-outline-info" href="trade_list.php">거래내역</a>
    </div>

    <div class="list-group">
        @if($data)
            @foreach($data as $item)
            <a href="#" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">{{$item['title']}}</h5>
                    <span>{{$item['price']}} 원</span>
                </div>
                <div class="d-flex w-100 justify-content-between">
                    <small>{{$item['registration_date']}}</small>
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
