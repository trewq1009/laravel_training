@include('layout.head')
@include('layout.header')

<section class="container">
    <form style="margin: 3rem 0 0 0" action="{{htmlspecialchars("/trade/detail/".$board->no)}}" method="post">
        @csrf
        @if ($errors->has('boardNo'))
            <span class="text-danger">{{ $errors->first('boardNo') }}</span>
        @endif
        <input type="hidden" name="boardNo" value="{{$board->no}}">
        <div style="margin: 0 0 1rem 0">
            <button type="submit" class="btn btn-primary">거래 신청</button>
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">상품명</span>
            <p class="form-control" style="margin: 0">{{$board->product_name}}</p>
            <span class="input-group-text" id="basic-addon1">등록일</span>
            <p class="form-control" style="margin: 0">{{$board->registration_date}}</p>
        </div>

        <div class="input-group mb-3">
            <span class="input-group-text" id="basic-addon1">가격</span>
            <p class="form-control" style="margin: 0">{{$board->price}}</p>
        </div>

        <div class="input-group">
            <span class="input-group-text">상품 이미지</span>
            <div class="text-center">
                <img src="{{asset("images/$image->image_name")}}" alt="images" class="img-thumbnail" >
            </div>
        </div>

        <div class="input-group">
            <span class="input-group-text">상품 설명</span>
            <p class="form-control" style="margin: 0">{{nl2br($board->product_information)}}</p>
        </div>
    </form>
</section>


@include('layout.footer')
