@include('layout.head')
@include('layout.header')
<?php if(!empty($data)) : var_dump($data); endif; ?>
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
