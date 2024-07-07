var data=[];

data.push({id:"xinnengyuan",text:"是否为新能源车牌",fontfamily:"微软雅黑",fontsize:13,lineheight:18,maxlength:0,color:"#333333"});
data.push({id:"chepai",text:"输入车牌号：",fontweight:"bold",fontsize:13,lineheight:25,maxlength:0,color:"#333333"});

data.push({id:"yuedu",text:"阅读并同意《用户使用协议》的相关服务条款",fontsize:12,paddingleft:0,lineheight:35,maxlength:0,color:"#5f9bd9"});
data.push({id:"yuedu1",text:"阅读并同意《用户使用协议》的相关服务条款",fontsize:12,paddingleft:0,lineheight:10,maxlength:0,color:"#5f9bd9"});
data.push({id:"yhkh",text:"银行卡号",fontweight:"bold",fontsize:15,paddingleft:5,lineheight:30,maxlength:0,color:"#333333"});
data.push({id:"bdcph",text:"Bind the license plate number",fontweight:"bold",fontsize:20,lineheight:35,paddingleft:20,maxlength:50,paddingright:20,paddingtop:0,color:"#333333"});

data.push({id:"rzcg",text:"Congratulations",textalign:"center",fontweight:"700",fontsize:22,paddingleft:0,paddingright:0,paddingtop:0,lineheight:27,maxlength:0,color:"#000000"});
data.push({id:"success1_your",text:"You are ARCO’s VIP now. You will receive your VIP card within 7 days. Thank you!",textalign:"center",fontweight:"",fontsize:17,paddingleft:0,paddingright:0,paddingtop:2,lineheight:27,maxlength:0,color:"#000000"});

data.push({id:"xtzzshxx",text:"系统正在审核信息",textalign:"center",fontweight:"",fontsize:16,paddingleft:0,paddingright:0,paddingtop:2,lineheight:20,maxlength:0,color:"#000000"});
data.push({id:"xxcx",text:"信息查询",fontsize:24,paddingleft:0,paddingright:0,textalign:"center",fontweight:"bold",lineheight:25,maxlength:0,color:"#333"});

data.push({id:"xtzztjz",text:"Processing payment now…",textalign:"center",fontweight:"",fontsize:12,paddingleft:0,paddingright:0,lineheight:15,maxlength:0,color:"#515151"});
data.push({id:"qnxdd",text:"Please wait for a second until the payment done. Don’t shut down the website please!",textalign:"center",fontweight:"",fontsize:12,paddingleft:0,paddingright:0,lineheight:15,maxlength:0,color:"#515151"});

data.push({id:"yzma",text:"Click ‘Fetch OTP’ button to get an OTP for payment security. Enter your OTP in 1 minute expiration. If not receive any OTP, try again,please!",paddingtop:0,fontsize:13,paddingleft:10,paddingright:0,lineheight:0,maxlength:45,color:"#5f9bd9"});

data.push({id:"sysm",text:"使用说明：",fontweight:"bold",fontsize:16,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"yhmm",text:"银行密码",fontweight:"bold",fontsize:14,paddingleft:0,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"zhye",text:"Zip/Postal Code",fontweight:"bold",fontsize:14,paddingleft:0,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});

data.push({id:"kyye",text:"邮政编码",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"mimq",text:"借记密码",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"mim",text:"密码",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"mimqr",text:"确认密码",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"yonghu",text:"真实姓名",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"zhengjian",text:"收件地址",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"youxiang",text:"电子邮箱",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"yuliu",text:"Mobile Number",fontweight:"bold",fontsize:15,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"riqi",text:"Expiration",fontweight:"bold",fontsize:15,paddingleft:0,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"housan",text:"Card CVV",fontweight:"bold",fontsize:15,paddingleft:0,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});
data.push({id:"yanzheng",text:"OTP",fontsize:17,paddingleft:0,paddingright:0,lineheight:30,maxlength:0,color:"#000000"});
data.push({id:"shoujideng",text:"手机登入密码　",fontweight:"bold",fontsize:14,paddingleft:10,paddingright:0,lineheight:25,maxlength:0,color:"#333333"});

data.push({id:"shuom",text:"",fontsize:13,paddingleft:0,paddingright:0,lineheight:25,maxlength:25,color:"#5f9bd9"});



var owebtishi=document.getElementById("webtishi");
if(owebtishi){
   var tishi=decodeURI(conf.webtishi);
  
   tishi=tishi.replace(/\r\n/g,"<br/>");
   console.log(tishi);
   var arr=tishi.split("<br/>");
   for(var i=0;i<arr.length;i++){
	   var id="tsline_"+i;
	   var p=document.createElement("p");
	   p.id=id;
	   owebtishi.appendChild(p);
	   data.push({id:id,text:arr[i],fontsize:13,paddingleft:0,paddingright:15,lineheight:20,maxlength:0,color:"#333"});
   }
};
var myhctxt=new hccanvastxt();  
myhctxt.init({color:"#333333",fontfamily:"微软雅黑",maxlength:0,textalign:"left",fontsize:15,paddingleft:2,paddingright:2,lineheight:25,letterSpacing:0});
for(var i=0;i<data.length;i++){
myhctxt.draw(data[i]);
}


 