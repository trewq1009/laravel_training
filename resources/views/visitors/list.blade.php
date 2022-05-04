@include('layout.head')
@include('layout.header')
<link rel="stylesheet" href="resources/css/app.css">
<section class="container">
    <form method="POST" action='<?php echo htmlspecialchars('/visitors');?>' id="methodForm">
        @csrf
        <div>
            <label for="content" class="form-label">방명록</label>
            <div class="input-group">
                <textarea class="form-control mainArea" aria-label="With textarea" id='content' name="content"placeholder="글을 입력해 주세요."></textarea>
                <span class="input-group-text">
                    <button type="button" class="btn btn-secondary" onclick='btnEvent()' name="btn" value="insert">등록</button>
                </span>
            </div>

            @if(!\Illuminate\Support\Facades\Auth::check())
            <div class="input-group mb-3">
                <span class="input-group-text" id="basic-addon1">게스트 패스워드</span>
                <input type="password" class="form-control" id="visitorsPassword" name="visitorsPassword" required>
            </div>
            @endif

        </div>
    </form>

    <div style="margin: 1rem 0 0 0;">
        <ul class="list-group">
            @foreach($data as $item)
            <li class="list-group-item" data-board="{{$item->no}}">
                <div class="firstBox">
                    <div data-board="{{$item->no}}" data-user="{{$item->user_no}}" data-type="{{$item->user_type}}">
                        <span>{{$item->user_name}}</span>
                        <div>
                            @if($item->user_type == 'g')
                            <input type="password" id="boardPassword{{$item->no}}" name="boardPassword" style="height: 1.25rem; width: 10rem;" placeholder="password">
                            @endif
                            <small onclick="updateHtml(this)">수정</small>
                            <small onclick="deleteAction(this)">삭제</small>
                        </div>
                    </div>
                    <small>{{$item->registration_date}}</small>
                </div>

                <div id="contentBox{{$item->no}}">
                    <p>{{$item->content}}</p>
                </div>

                <div>
                    <a href="javascript:void(0);" onclick="commentList(this)" style="color: red;">댓글 0</a>
                </div>

                <div id="commentBox{{$item->no}}" class="commentBox">
                    <div id="commentBlock">
                        <ul class="list-group" data-board="{{$item->no}}"></ul>
                    </div>
                    <div class="input-group">
                        <textarea class="form-control" aria-label="With textarea" id="comment{{$item->no}}" placeholder="글을 입력해 주세요."></textarea>
                        <span class="input-group-text">
                            @if(!\Illuminate\Support\Facades\Auth::check())
                            <input type="password" name="boardPassword" style="height: 1.25rem; width: 10rem;" placeholder="password">
                            @endif
                            <button type="button" class="btn btn-secondary" onclick='commentEvent(this)' data-board="{{$item->no}}">등록</button>
                        </span>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

</section>
<script>
    // 방명록 등록
    function btnEvent() {
        if(!{{\Illuminate\Support\Facades\Auth::check()}}) {
            const result = window.confirm('비회원으로 글을 등록 하시겠습니까?');
            if(!result) {
                return;
            }
            // 비회원 글 등록
            document.querySelector('#methodForm').submit();
            return;
        }
        // 회원 글 등록
        document.querySelector('#methodForm').submit();
    };

    // 댓글 불러오기
    function commentList(event, page = 1) {
        // 댓글 카운트 확인 후 없으면 댓글이 없습니다.
        // 있으면 댓글 보여주기
        const board_num = event.parentElement.parentElement.dataset.board;

        $.ajax({
            type : 'GET',
            url : '/ajax/visitors/list',
            data : {
                board_num : board_num,
                page : page
            },
            success : result => {
                const re_data = JSON.parse(result);
                if(re_data.status === 'success') {
                    const comment_box = document.querySelector('#commentBox'+board_num).firstElementChild.firstElementChild;
                    let htmlData;
                    re_data.data.data.forEach(item => {
                        htmlData += "<li class='list-group-item'>" +
                                    "<div class='firstBox'>" +
                                        "<div data-board='"+item.no+"' data-user='"+item.user_no+"' data-type='"+item.user_type+"'>" +
                                            "<div>" +
                                                "<span>"+item.user_name+"</span>" +
                                                "<small>+item.registration_date+</small>" +
                                                "<small onclick='commentList(this)'>답글</small>" +
                                            "</div>" +
                                            "<div>";

                        if(item.user_type === 'g') {
                            htmlData += "<input type='password' name='boardPassword' style='height:1.25rem; width:10rem;' placeholder='password'>";
                        }

                        htmlData +=                 "<small onclick='updateHtml(this)'>수정</small>" +
                                                "<small onclick='deleteAction(this)'>삭제</small>" +
                                            "</div>" +
                                        "</div>" +
                                    "</div>" +
                                    "<div id='contentBox"+item.no+"'>" +
                                        "<p>item.content</p>" +
                                    "</div>" +
                                    "<div id='commentBox"+item.no+"' class='commentBox'>" +
                                        "<div id='commentBlock'>" +
                                            "<ul class='list-group' data-board='"+item.no+"'></ul>" +
                                        "</div>" +
                                        "<div class='input-group'>" +
                                            "<textarea class='form-control' aria-label='With textarea' id='comment"+item.no+"' placeholder='글을 입력해 주세요.'></textarea>" +
                                            "<span class='input-group-text'>";

                        if(!{{\Illuminate\Support\Facades\Auth::check()}}) {
                            htmlData += "<input type='password' name='boardPassword' style='height: 1.25rem; width:10rem;' placeholder='password'>";
                        }

                        htmlData += "<button type='button' class='btn btn-secondary' onclick='commentEvent(this)' data-board='"+item.no+"'>등록</button>" +
                                            "</span>" +
                                        "</div>" +
                                    "</div>" +
                                "</li>";

                    })

                    if(Math.ceil(re_data.data.total / re_data.data.per_page) > 0) {
                        if(re_data.data.current_page > 1) {
                            const prv = re_data.data.current_page - 1;
                            htmlData += "<li class='page-item'><a class='page-link' href='javascript:void(0)' onclick='commentList(this, "+prv+")'>Previous</a></li>";
                        }
                        if(re_data.data.current_page < Math.ceil(re_data.data.total / re_data.data.per_page)) {
                            const next = re_data.data.current_page + 1;
                            htmlData += "<li class='page-item'><a class='page-link' href='javascript:void(0)' onclick='commentList(this, "+next+")'>Next</a></li>";
                        }
                    }

                    comment_box.innerHTML = htmlData ?? '';
                    const comment_section = document.querySelector('#commentBox'+board_num);
                    comment_section.style.display = 'block';
                } else {
                    window.alert('댓글 불러오기 에러');
                    console.log(re_data.message);
                }
            },error : e => {
                console.log(e);
                window.alert('댓글을 불러오는중 에러가 발생했습니다.');
            }
        });
    }

    // 댓글 등록
    async function commentEvent(event) {
        const parent_board_num = event.dataset.board;
        const comment = document.getElementById('comment'+parent_board_num).value;
        let password;
        if(!{{\Illuminate\Support\Facades\Auth::check()}}) {
            const result = window.confirm('비회원으로 댓글을 등록 하시겠습니까?');
            if (!result) {
                return;
            }
            // 비회원 댓글 등록
            password = event.previousElementSibling.value;
            if (password === '') {
                window.alert('비회원은 패스워드가 필수 입니다.');
                return;
            }
        }
        await $.ajax({
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            type: 'POST',
            url: '/ajax/visitors/comment',
            data: {
                parent_no: parent_board_num,
                comment: comment,
                comment_password: password ?? ''
            },
            success: result => {
                const re_data = JSON.parse(result);
                console.log(re_data);
                // if (re_data.status === 'success') {
                //     window.alert(re_data.message);
                //     window.location.reload();
                // } else {
                //     window.alert(re_data.message);
                // }
            }, error: e => {
                console.log(e);
                window.alert('에러가 발생했습니다.')
            }
        });
    }

</script>

@include('layout.footer')
