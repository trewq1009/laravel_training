@include('layout.admin.head')
@include('layout.admin.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/');?>' method="post" id="methodForm">
        @csrf
        <input type="hidden" name="withdrawalNo" value="{{$data->no}}">
        <div class="mb-3">
            <label for="userId" class="form-label">ID</label>
            <input type="text" class="form-control" value="{{$data->user_id}}" name="userId" id="userId" readonly required>
        </div>
        <div class="mb-3">
            <label for="userName" class="form-label">Name</label>
            <input type="text" class="form-control" value="{{$data->user_name}}" name="userName" id="userName" readonly required>
        </div>
        <div class="mb-3">
            <label for="withdrawalMileage" class="form-label">Total Amount</label>
            <input type="text" class="form-control" value="{{$data->withdrawal_mileage}}" name="withdrawalMileage" id="withdrawalMileage" readonly required>
        </div>
        <div class="mb-3">
            <label for="userRequested" class="form-label">Requested AT</label>
            <input type="text" class="form-control" value="{{$data->registration_date}}" id="userRequested" readonly required>
        </div>
        <div class="mb-3">
            <label for="bankName" class="form-label">Bank</label>
            <input type="text" class="form-control" value="{{$data->bank_name}}" name="bankName" id="bankName" readonly required>
        </div>
        <div class="mb-3">
            <label for="bankAccount" class="form-label">Bank Account Number</label>
            <input type="text" class="form-control" value="{{$data->bank_account_number}}" name="bankAccount" id="bankAccount" readonly required>
        </div>
        <button type="button" onclick="btnAction(this)" class="btn btn-primary">승인</button>
        <button type="button" class="btn btn-primary">취소</button>
    </form>
</section>

<script>
    function btnAction(event) {
        if(!window.confirm('승인 하시겠습니까?')) return;
        document.querySelector('#methodForm').action = "/admin/withdrawal/detail/{{$data->no}}";
        document.querySelector('#methodForm').submit();
    }
</script>

@include('layout.admin.footer')
