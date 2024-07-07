$(document).ready(function(){//在文档加载完毕后执行
    $(window).scroll(function(){//开始监听滚动条
        var topp = $(document).scrollTop();
        if(topp >= 44){
            $("#ac-localnav").addClass('ac-localnav-sticking')
            var x = 0.39951279759407043 + topp/1000/1.2;
            var y = 290.9872131347656 - topp/1.2;
            var z = 89.51278686523438 + topp/1.2;
            $("#element_18").css("transform","matrix("+x+",0,0,"+x+","+y+",-"+z+")");
            $("#element_18").css("opacity",0.5-topp/700);
        }else{
            $("#ac-localnav").removeClass('ac-localnav-sticking')
            $("#element_18").css("transform","matrix(0.39951279759407043,0,0,0.39951279759407043,290.9872131347656,-89.51278686523438)");
            $("#element_18").css("opacity",0.9820763922660176);
        }
    })
})