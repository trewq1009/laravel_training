@include('layout.admin.head')
@include('layout.admin.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/');?>' method="get" id="formMethod">
        <input type="hidden" name="userNo" value="" id="userNo">
        <table class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">ID</th>
                <th scope="col">WITHDRAWAL MILEAGE</th>
                <th scope="col">STATUS</th>
                <th scope="col">REG_DT</th>
                <th scope="col">VIEW</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data->data as $item)
                <tr>
                    <th scope="row">{{$item->no}}</th>
                    <td>{{$item->id}}</td>
                    <td>{{$item->withdrawal_mileage}}</td>
                    <td>{{$item->status}}</td>
                    <td>{{$item->registration_date}}</td>
                    <td>
                        <a href="/admin/withdrawal/detail/{{$item->no}}" class="btn btn-outline-info">VIEW</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </form>
    <div>
        {{$page->links()}}
    </div>
</section>

@include('layout.admin.footer')
