<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }}-积分商城</title>
    <link href="{{asset('css/common.css')}}" rel="stylesheet" type="text/css" media="screen,projection" />
    <script type="text/javascript" src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
</head>

<body>
<div class="content">
    <ul class="list_title">
        <li class="slt" data-type="up"><span>积分价格升序</span><em class="up"></em></li>
        <li  data-type="down"><span>积分价格降序</span><em class="down"></em></li>
    </ul>

    <ul class="cp_box"></ul>

    <div class="bth"></div>

    <div class="menu_bt">
        <a class="i1 slt" href="#">全部商品</a>
        <a class="i2" href="{{ url('/wechat/me') }}">我的</a>
    </div>
</div>

<!-- 遮罩 -->
<div id="cover" class="cover2">
    <div class="loading"></div>
</div>

<script type="text/javascript">
    var loading = false,
        sortType = 'up',
        totalHeight = 0,
        loadMore = true,
        total = 0,
        offset = 0,
        limit = 10;

    $(document).ready(function(){
        // 获取列表
        getList();

        // 排序切换
        $(".list_title li").click(function(){
            if (sortType === this.dataset.type) return false;

            $(".list_title li").removeClass("slt");
            $(this).addClass("slt");

            sortType = this.dataset.type;
            refreshList();
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

    // 刷新列表
    function refreshList() {
        total = 0;
        offset = 0;
        loadMore = true;
        $('.cp_box').empty();
        getList();
    }

    // 获取列表
    function getList() {
        if (!loadMore) return false;

        loading = true;
        $('#cover').show();

        $.ajax({
            type: 'post',
            url: 'commodity_list',
            data: {
                limit: limit,
                offset: offset,
                sort: sortType
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 200) {
                    var li = '';
                    var data = res.data;
                    var list = data.list;
                    total += list.length;

                    $(list).each(function () {
                        li += '<li>' +
                            '<img src="' + this.image + '" alt=""/>' +
                            '<div class="r">' +
                            '<p>' + this.name + '</p>' +
                            '<div class="num">库存剩余：' + this.stock + '件</div>' +
                            '<div class="bt">' +
                            '<div class="integral"><em>' + parseInt(this.price) + '</em>积分</div>' +
                            '<a href="detail?id=' + this.id +'">商品详情</a>' +
                            '</div>' +
                            '</div>' +
                            '</li>';
                    });

                    $('.cp_box').append(li);

                    if (total >= data.total) loadMore = false;
                }

                loading = false;
                $('#cover').hide();
            }
        })
    }
</script>

</body>
</html>