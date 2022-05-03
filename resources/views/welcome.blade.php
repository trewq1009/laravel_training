@include('layout.head')
@include('layout.header')

<section class="container">
    <div>
        <h3>Hello PHP</h3>
        @if(!empty($message))
        <span>{{$message}}</span>
        @endif
    </div>
    @auth
    <div>
        <h5>유저 : {{$name}}</h5>
        <h5>사용 중인 마일리지 : {{$using_mileage}}</h5>
        <h5>사용 가능 마일리지 : {{$use_mileage}}</h5>
        <h5>출금 가능 마일리지 : {{$real_mileage}}</h5>
    </div>
    @endauth
</section>



@include('layout.footer')
