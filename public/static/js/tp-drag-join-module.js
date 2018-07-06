/*用自定义属性data-eidtid记录区分打开的diy编辑触发器*/
var dataEidtid=['tpd-poster','tpd-banner','tpd-navs','tpd-goods','tpd-window','tpd-txtnav','tpd-activity','tpd-coupon','tpd-search','tpd-storeh','tpd-message','tpd-footmenu'];
var diyHtml={
    'tpd-poster':['<a href="javascript:;" class="tpdm-postyle tpdm-postyle2"> <img src="/public/static/images/pic-poster.jpg" alt="" /></a>'],

    'tpd-banner':['<div class="tpdm-carousel"><ul class="tpd-banner-list"> <li><a href="javascript:;"><img src="/public/static/images/banner1.jpg" alt="" /></a></li><li><a href="javascript:;"> <img src="/public/static/images/banner2.jpg" alt="" /></a></li></ul><div class="page-num"><span></span><span></span></div> </div><div class="tpdm-carousel-js"></div>'],

    'tpd-navs':['<div class="tpdm-navstyle tpdm-navstyle5 clearfix"> <a href="javascript:;"> <div class="tpdm-navstyle-ico"><img src="/public/static/images/ico-nav1.jpg" alt="" /></div><p class="tpdm-navstyle-cont">分类</p> </a> <a href="javascript:;"> <div class="tpdm-navstyle-ico"><img src="/public/static/images/ico-nav2.jpg" alt="" /></div> <p class="tpdm-navstyle-cont">关注</p> </a> <a href="javascript:;"> <div class="tpdm-navstyle-ico"><img src="/public/static/images/ico-nav3.jpg" alt="" /></div> <p class="tpdm-navstyle-cont">收藏</p> </a> <a href="javascript:;"> <div class="tpdm-navstyle-ico"><img src="/public/static/images/ico-nav4.jpg" alt="" /></div> <p class="tpdm-navstyle-cont">购物车</p> </a> <a href="javascript:;"> <div class="tpdm-navstyle-ico"><img src="/public/static/images/ico-nav5.jpg" alt="" /></div> <p class="tpdm-navstyle-cont">个人中心</p></a> </div>'],

    'tpd-goods':['<div class="tpdm-gdstyle"><ul class="tpdm-goods-nav tpdm-goods-nav3"><li>列表名称1</li> <li>列表名称2</li> <li>列表名称3</li></ul> <div class="tpdm-goods-list tpdm-goods-list1"> <ul class="tpdm-goods-wrap clearfix"> <li> <a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a> <a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a> <div class="tpdm-goods-des"> <div class="tpdm-goods-price">￥128.00</div> <a class="tpdm-goods-like" href="javascript:;">看相似</a> </div> </li> <li> <a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a> <a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a> <div class="tpdm-goods-des"> <div class="tpdm-goods-price">￥128.00</div> <a class="tpdm-goods-like" href="javascript:;">看相似</a> </div></li> <li> <a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a><a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a><div class="tpdm-goods-des"><div class="tpdm-goods-price">￥128.00</div> <a class="tpdm-goods-like" href="javascript:;">看相似</a> </div></li> <li><a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a> <a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a> <div class="tpdm-goods-des"><div class="tpdm-goods-price">￥128.00</div><a class="tpdm-goods-like" href="javascript:;">看相似</a> </div></li> </ul></div></div>'],

    'tpd-window':[
        '<div class="tpdm-wstyle tpdm-wstyle-wrap1"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a> </div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap2"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap3"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap4"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap5"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap6"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a> <a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle4"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap7"> <a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a> <a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle4"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap8"><div class="clearfix"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle4"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div><a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>',
        '<div class="tpdm-wstyle tpdm-wstyle-wrap9"><a href="javascript:;" class="tpdm-wstyle1"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle2"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle3"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a><a href="javascript:;" class="tpdm-wstyle4"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /></a></div>'
    ],

    'tpd-txtnav':['<div class="tpdm-txtnav-list"><a href="javascript:;">查看更多</a></div>'],

    'tpd-activity':[
        '<div class="tpdm-acstyle"><div class="tpdm-activity-title">拼团标题（没有标题则此栏不显示）</div><div class="tpdm-goods-list tpdm-goods-list1"><ul class="tpdm-goods-wrap clearfix" id="nxkhh"><li><a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a><a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a><div class="tpdm-goods-des"><div class="tpdm-goods-price">￥12222228.00</div><div class="tpdm-sold-num">已拼1000000</div></div> <div class="tpdm-goods-mes"> <a href="javascript:;">去拼单 ></a><span>100团</span><div class="tpdm-acbuyer"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /> ...</div></div></li><li> <a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a><a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a><div class="tpdm-goods-des"><div class="tpdm-goods-price">￥12222228.00</div> <div class="tpdm-sold-num">已拼1000000</div> </div><div class="tpdm-goods-mes"><a href="javascript:;">去拼单 ></a><span>100团</span><div class="tpdm-acbuyer"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" /> ...</div> </div> </li> <li> <a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a> <a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a><div class="tpdm-goods-des"><div class="tpdm-goods-price">￥12222228.00</div> <div class="tpdm-sold-num">已拼1000000</div></div><div class="tpdm-goods-mes"><a href="javascript:;">去拼单 ></a><span>100团</span><div class="tpdm-acbuyer"><img src="/public/static/images/pic-wstyle-list1.jpg" alt="" />... </div></div></li><li><a class="tpdm-goods-pic" href="javascript:;"><img src="" alt="" /></a><a href="javascript:;" class="tpdm-goods-name">联想台式机主机 GTX1080Ti游戏主机DIY组装机 i7 6700</a> <div class="tpdm-goods-des"> <div class="tpdm-goods-price">￥12222228.00</div> <div class="tpdm-sold-num">已拼1000000</div> </div> <div class="tpdm-goods-mes"> <a href="javascript:;">去拼单 ></a> <span>100团</span> <div class="tpdm-acbuyer"> <img src="/public/static/images/pic-wstyle-list1.jpg" alt="" />... </div> </div> </li> </ul> </div> </div>',
        '<div class="tpdm-acstyle"> <div class="tpdm-seckill-title"><label>秒杀专场</label><span id="flash_time">12点专场</span> <ul class="tpdm-seckill-time" id="hms"> <li id="time_h">12</li> <li id="time_m">05</li> <li id="time_s">18</li> </ul> <a href="/Mobile/Activity/flash_sale_list.html">更多 ></a> </div> <div class="tpdm-seckill-list" id="flash_list"> <a href="javascript:;"> <img src="" alt="" /><span>￥158</span><i>￥258</i></a><a href="javascript:;"><img src="" alt="" /><span>￥158</span> <i>￥258</i></a><a href="javascript:;"><img src="" alt="" /><span>￥158</span><i>￥258</i></a><a href="javascript:;"><img src="" alt="" /><span>￥158</span><i>￥258</i></a></div></div>'
    ],

    'tpd-coupon':[
        '<ul class="tpdm-coupon-list tpdm-coupon-lis1"><li><div class="tpdm-coupon-wrap"><span><i>￥</i>100</span><label>满200使用</label><a href="javascript:;">立即领取</a></div></li><li><div class="tpdm-coupon-wrap"><span><i>￥</i>100</span><label>满200使用</label><a href="javascript:;">立即领取</a></div></li><li><div class="tpdm-coupon-wrap"> <span><i>￥</i>100</span><label>满200使用</label><a href="javascript:;">立即领取</a></div></li> </ul>',
        '<ul class="tpdm-coupon-list tpdm-coupon-lis2"><li><div class="tpdm-coupon-wrap"><span><i>￥</i>100</span><label>满200使用</label><a href="javascript:;">立即领取</a></div></li><li><div class="tpdm-coupon-wrap"><span><i>￥</i>100</span><label>满200使用</label><a href="javascript:;">立即领取</a></div></li></ul>'
    ],

    'tpd-search':['<div class="tpd-search-warp clearfix"><a class="tpd-logo" href="javascript:;"><img src="/public/static/images/logo.png" alt="" /></a><form class="tpd-search-form" action=""><i class="ico-head-search"></i><a href="/mobile/Goods/ajaxSearch.html"><input type="text" placeholder="请输入您所搜索的商品" /></a></form><a class="tpd-personal-warp" href="/Mobile/User/login.html"> <span>登录</span> <i class="ico-head-personal"></i> </a></div>'],

    'tpd-storeh':['<div class="tpd-store-head"><div class="tpd-storeh-cont tpd-storeh-cont1"><a class="tpd-storeh-logo" href="javascript:;"><img src="/public/static/images/logo.png" alt="" /></a><div class="tpd-storeh-name"><span>店铺名称</span><i class="tpd-storeh-collect">收藏</i></div><div class="tpd-storeh-des">一句话介绍你的店铺</div></div></div>'],

    'tpd-message':['<div class="tpdm-messtyle clearfix"><div class="tpdm-mes-logo"><img src="/public/static/images/pic-mes-logo.jpg" alt="" /></div><ul class="tpdm-mes-title"><li class="tpdm-mesname1">团购</li><li class="tpdm-mesname2">品牌</li></ul><div class="tpdm-mes-cont"><a class="ellipsis1 tpdm-mescont1" href="javascript:;">2998 ! 全新OPPO R11团购仅需2998元！</a><a class="ellipsis1 tpdm-mescont2" href="javascript:;">法国原装进口迪奥香水，最新款等你来免费试用！</a></div></div>'],

    'tpd-footmenu':['<div class="tpd-footer tpd-footer1"><a class="footmenu-ac" href="/mobile/Index/index"><i class="ico-foot1"></i><span>首页</span></a><a href="/mobile/Goods/categoryList.html"><i class="ico-foot2"></i><span>分类</span></a><a class="tpd-footer-find" href="/mobile/Goods/ajaxSearch"> <i class="ico-foot3"></i><span>发现</span></a><a href="/mobile/Cart/index"><i class="ico-foot4"></i><span>购物车</span></a><a href="/Mobile/User/login"> <i class="ico-foot5"></i><span>我的</span></a></div>']

}

//轮播
function initSlide(parent,ele,t){// ele :id ,time:时间（毫秒）
    var scriptHtml="<script>swiperSlide("+ele+","+t+")</script>";
    parent.find('.tpdm-carousel-js').html(scriptHtml);
}
function swiperSlide(ele,t) {
    var carousel=$(ele);
    var page=carousel.find('.page-num');
    setPage(carousel,page);
    carousel.swipeSlide({
        continuousScroll:true,
        speed : t,
        transitionType : 'cubic-bezier(0.22, 0.69, 0.72, 0.88)',
        callback : function(i,sum,me){
            page.find('span').eq(i).addClass('page-ac').siblings().removeClass('page-ac');
        }
    });
}
function setPage(ele1,ele2){  //生成圆点
    ele2.html('');
    for(var i=0,j=ele1.find('li').length;i<j;i++){
        ele2.append("<span></span>");
    }
    ele2.find('span').eq(0).addClass('page-ac');
}