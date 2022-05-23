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
                <th scope="col">EMAIL STATUS</th>
                <th scope="col">STATUS</th>
                <th scope="col">REG_DT</th>
                <th scope="col">VIEW</th>
            </tr>
            </thead>
            <tbody>
            @foreach($data as $item)
            <tr>
                <th scope="row">{{$item->no}}</th>
                <td>{{$item->id}}</td>
                <td>{{$item->email_status}}</td>
                <td>{{$item->status}}</td>
                <td>{{$item->registration_date}}</td>
                <td>
                    <button type="button" onclick="memberDetail(this)" name="viewUser" value="{{$item->no}}" class="btn btn-outline-info">Info</button>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </form>
    <div>
        {{$data->links()}}
    </div>
</section>

<script>
    function memberDetail(event) {
        const user_no = event.value;
        document.querySelector('#userNo').value = user_no
        document.querySelector('#formMethod').action = '/admin/member/list/' + user_no;
        document.querySelector('#formMethod').submit();
    }
</script>

@include('layout.admin.footer')
