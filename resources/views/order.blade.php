<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" id="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ config('app.name') }}-我的订单</title>
    <link href="{{asset('css/common.css')}}" rel="stylesheet" type="text/css" media="screen,projection" />
    <script type="text/javascript" src="{{asset('js/jquery-1.11.1.min.js')}}"></script>
</head>

<body>
<div class="content">
    <ul class="list_title2">
        <li class="slt" data-status="all"><span>全部</span></li>
        <li data-status="1"><span>已兑换</span></li>
        <li data-status="2"><span>已完成</span></li>
    </ul>

    <ul class="cp_box2"></ul>
</div>

<!-- 遮罩 -->
<div id="cover" class="cover2">
    <div class="loading"></div>
</div>

<script type="text/javascript">
    var loading = false,
        status = 'all',
        totalHeight = 0,
        loadMore = true,
        total = 0,
        offset = 0,
        limit = 10;

    $(document).ready(function(){
        // 获取列表
        getList();

        // 状态选择
        $(".list_title2 li").click(function(){
            if (status === this.dataset.status) return false;

            $(".list_title2 li").removeClass("slt");
            $(this).addClass("slt");

            status = this.dataset.status;
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
        $('.cp_box2').empty();
        getList();
    }

    // 获取列表
    function getList() {
        if (!loadMore) return false;

        loading = true;
        $('#cover').show();

        $.ajax({
            type: 'post',
            url: 'order_list',
            data: {
                limit: limit,
                offset: offset,
                status: status === 'all' ? '' : status
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
                            '<div class="num">订单号：' + this.order_id + '</div>' +
                            '<div class="time">' + this.created_at + '</div>' +
                            '<div class="bt">' +
                            '<div class="integral"><em>' + parseInt(this.amount) + '</em>积分</div>' +
                            '<span>' + (this.status === 1 ? '已兑换' : '已完成') + '</span>' +
                            '</div>' +
                            '</div>' +
                            '</li>';
                    });

                    $('.cp_box2').append(li);

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
