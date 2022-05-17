<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Home</a>
                    </li>
                    @auth
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">User List</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin">Withdrawal List</a>
                    </li>
                    @endauth
                </ul>

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @auth
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/logout">로그아웃</a>
                    </li>
                    @endauth
                    @guest
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/register">관리자 등록</a>
                    </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>
<body>
