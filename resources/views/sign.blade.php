<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }}-签到</title>
    <link href="{{asset('css/common.css')}}" rel="stylesheet" type="text/css" media="screen,projection" />
    <script type="text/javascript" src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
</head>

<body>
<div class="content">
    <div class="sign_in">
        <p>我的积分<strong>{{ $point }}</strong></p>
        <div class="btn">
            @if ($isSign == true)
                <input class="b2" type="button" value="已签到">
            @else
                <input class="b1" type="button" value="点击签到" id="sign">
            @endif
        </div>
    </div>
    <div class="signin_box">
        <div class="l">
            <span>已连续签到</span>
            <p><em>{{ $signDay }}</em>天</p>
        </div>
        <div class="r">
            <ul>
                @foreach ($signDays as $day)
                <li>{{$day}}</li>
                @endforeach
            </ul>
            <div class="bg">
                <img class="s_bg" src="{{asset('images/sign_01.png')}}">
                <span style=" width:{{$signWidth}}%;"><img class="s_bg2" src="{{asset('images/sign_02.png')}}"></span>
            </div>
        </div>
    </div>

    <div class="block">
        <div class="title_1 mt20">
            <span>积分记录</span>
            <div class="menu_slt">
                <span id="listType">全部</span>
                <div class="menu_open">
                    <em data-type="all">全部</em>
                    <em data-type="in">获取</em>
                    <em data-type="out">使用</em>
                    <div class="ico"></div>
                </div>
            </div>
        </div>

        <ul class="num_list"></ul>
    </div>
</div>

<!-- 遮罩 -->
<div id="cover" class="cover2">
    <div class="loading"></div>
</div>

</body>
</html>

<script type="text/javascript">
    var loading = false,
        queryType = 'all',
        totalHeight = 0,
        loadMore = true,
        total = 0,
        offset = 0,
        limit = 10,
        listType = {
            'all': '全部',
            'in': '获取',
            'out': '使用',
        },
        pointType = {
            1: '签到',
            2: '购物',
            3: '刷卡'
        };

    $(document).ready(function(){
        // 请求列表
        getList();

        // 打开筛选
        $(".title_1 .menu_slt span").click(function(){
            $(".menu_open").show();
        });

        // 签到
        $('#sign').on('click', function () {
            $.ajax({
                type: 'post',
                url: 'sign_in',
                dataType: 'json',
                success: function (res) {
                    if (res.code === 200) {
                        alert('签到成功');
                        location.reload();
                    } else {
                        alert('签到失败');
                    }
                }
            })
        });

        // 加载
        $(window).scroll(function () {
            totalHeight = parseFloat($(window).height()) + parseFloat($(window).scrollTop());
            if ($(document).height() <= totalHeight && loadMore && !loading) {
                offset += limit;
                getList();
            }
        });
    });

    // 关闭筛选
    $(document).mouseup(function(e) {
        var _con = $('.menu_slt');
        if(!_con.is(e.target) && _con.has(e.target).length === 0){
            $(".menu_open").hide();
        }
    });

    // 选择
    $('.menu_open em').on('click', function () {
        var type = this.dataset.type;
        if (queryType === type) return false;
        queryType = type;
        $('#listType').html(listType[type]);
        $(".menu_open").hide();
        refreshList();
    });

    // 刷新列表
    function refreshList() {
        total = 0;
        offset = 0;
        loadMore = true;
        $('.num_list').empty();
        getList();
    }

    // 获取列表
    function getList() {
        if (!loadMore) return false;

        loading = true;
        $('#cover').show();

        $.ajax({
            type: 'post',
            url: 'point_list',
            data: {
                limit: limit,
                offset: offset,
                type: queryType
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    var li = '';
                    var data = res.data;
                    var list = data.list;
                    total += list.length;

                    $(list).each(function () {
                        var amount = parseInt(this.amount);
                        li += '<li>' +
                            '<div><span class="l">' + pointType[this.type] + '</span>' +
                            (amount < 0 ? '<em>' + amount + '</em>' : '<em class="add">+' + amount + '</em>') +
                            '</div>' +
                            '<p>' + this.created_at + '</p>' +
                            '</li>';
                    });

                    $('.num_list').append(li);

                    if (total >= data.total) loadMore = false;
                }

                loading = false;
                $('#cover').hide();
            }
        })
    }
</script>