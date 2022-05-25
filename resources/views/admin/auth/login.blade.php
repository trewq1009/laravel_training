@include('layout.admin.head')
@include('layout.admin.header')

<section class="container">
    <form action='<?php echo htmlspecialchars('/admin/login');?>' method="post" id="methodForm">
        @csrf
        <div class="mb-3">
            <label for="adminId" class="form-label">ID</label>
            <input type="text" class="form-control" name="adminId" id="adminId" required>
        </div>
        <div class="mb-3">
            <label for="adminPw" class="form-label">Password</label>
            <input type="password" class="form-control" name="adminPw" id="adminPw" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</section>

@include('layout.admin.footer')
