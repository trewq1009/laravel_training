@include('layout.head')
@include('layout.header')

<section class="container">
    <div>
        <h3>Hello PHP</h3>
    </div>
    @auth
    <div>
        <h5>유저 : {{Auth::id()}}</h5>
    </div>
    @endauth
</section>



@include('layout.footer')
