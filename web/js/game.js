var mySocket = {
	client:null,
	address:'',
	init:function(msg){
		var client = new WebSocket(mySocket.address);
		client.onopen = function(evt){
			trace('已经连接server');
			mySocket.client = client;
			if(msg){
				mySocket.client.send(msg);
			}
			//心跳
			setInterval(function(){
				var d = {};
				d.cmd = 0x002;
				d.data = {};
				mySocket.send(d);
			}, 1000*10);
		};
		client.onclose = function(evt){
			alert('server连接断开');
			mySocket.client = null;
			game.exit();
		};
		client.onmessage = function(evt){
			var arr = evt.data.split('@@');
			for(var i in arr){
				arr[i] && game.recv(JSON.parse(arr[i]));
			}
		};
		client.onerror = function(evt){
			alert('server连接出现错误');
			mySocket.client = null;
			game.exit();
		};
	},
	send:function(d){
		var msg = JSON.stringify(d) + "@@";
		if(mySocket.client){
			mySocket.client.send(msg);
		}else{
			mySocket.init(msg);
		}
	}
};

var game = new function(){
	var backLayer,resultLayer,controlLayer,loadingLayer,checkLayer,foeBitmap,selfBitmap,mid,wanjia,diannao;
	var loginLayer;
	var zbLayer, wanjiazb, wannozbBut, dnzb;//准备等控制
	var resultLayer,textField_All,textField_win,textField_fail,textField_draw;
	var shitouButton,jiandaoButton,buButton;
	var msgText,exitBut;
	var dataList = showList = [];
	var imgData = [//需要加载的图片
		{name:"bu",path:"images/bu.png"},
		{name:"jiandao",path:"images/jiandao.png"},
		{name:"shitou",path:"images/shitou.png"},
		{name:"title",path:"images/title.png"},
		{name:"ok_button",path:"images/ok_button.png"}
	];
	function initGame(){
		
	}
	
	function loginLayer(){
		loginLayer = new LSprite();
		
		loginLayer.graphics.drawRect(10,"green",[0,0,800,80],true,"black");//添加黑色背景
		loginLayer.graphics.drawRect(10,"green",[0,80,800,420],true,"#C3EEF9");//添加黑色背景
		addChild(loginLayer);
		
		var loginText = initTextLayer("请输入游戏ID:","black",25,100,120);
		loginText.stroke = true;
		loginText.lineWidth = 2;
		loginText.lineColor = "#FF0000";
		loginLayer.addChild(loginText);
		
		var idTextField = initTextLayer("","#FF0000",25,280,120);
		idTextField.setType(LTextFieldType.INPUT);
		idTextField.stroke = true;
		idTextField.lineWidth = 2;
		idTextField.lineColor = "#FF0000";
		loginLayer.addChild(idTextField);
		
		
		var bitmapDataUp = new LBitmapData(dataList["ok_button"],0,0,98,48);
		var bitmapUp = new LBitmap(bitmapDataUp);
		var bitmapDataOver = new LBitmapData(dataList["ok_button"],0,48,98,48);
		var bitmapOver = new LBitmap(bitmapDataOver);
		bitmapUp.scaleX = 0.5;
		bitmapOver.scaleX = 0.5;
		var idButton = new LButton(bitmapUp,bitmapOver);
		idButton.x = 450
		idButton.y = 120;
		loginLayer.addChild(idButton);
		idButton.addEventListener(LMouseEvent.MOUSE_DOWN,function(e){
			login(idTextField.text);
		});
	}
	
	function login(id){
		mid = parseInt(id);
		if(!mid){
			return;
		}
		var d = {};
		trace(id + ' 登录中。。');
		d.cmd = 0x101;
		d.data = {};
		d.data.mid = mid;
		mySocket.send(d);
	}
	
	/**
	*initCheckLayer，resultLayer文字缩减代码
	*/
	function initTextLayer(d,w,z,h,j){
		var textField=new LTextField();
		textField.text=d;
		textField.color=w;
		textField.font="微软雅黑";
		textField.weight="bold";
		textField.size=z;
		textField.x=h;
		textField.y=j;
		return textField;
	}
	function main(){
		//LGlobal.setDebug(true);
		if (LGlobal.canTouch){
			LGlobal.stageScale = LStageScaleMode.EXACT_FIT;
		}else{
			LGlobal.stageScale = LStageScaleMode.SHOW_ALL;
		}
		LGlobal.screen(LStage.FULL_SCREEN);
		//LMouseEventContainer.set(LMouseEvent.MOUSE_DOWN,true);
		//LMouseEventContainer.set(LMouseEvent.MOUSE_UP,true);
		//LMouseEventContainer.set(LMouseEvent.MOUSE_MOVE,true);


		backLayer=new LSprite();
		//backLayer.graphics.drawRect(10,"green",[0,0,800,500],true,"black");//添加黑色背景
		addChild(backLayer);


		//创建加载文件的进度条，并添加到第一层
		loadingLayer = new LoadingSample3();
		backLayer.addChild(loadingLayer);
		/**
		*读取图片资源文件
		*/
		LLoadManage.load(imgData,function(press){
			loadingLayer.setProgress(press);
		},
		function(result){
			dataList=result;
			backLayer.removeChild(loadingLayer);
			loadingLayer=null;//清空进度条
			loginLayer();
			
			showList[0] = new LBitmapData(dataList["shitou"]);
			showList[1] = new LBitmapData(dataList["jiandao"]);
			showList[2] = new LBitmapData(dataList["bu"]);
			
			//添加标题
			var titleBitmap = new LBitmap(new LBitmapData(dataList["title"]));
			titleBitmap.x=(LGlobal.width-titleBitmap.getWidth())/2;
			titleBitmap.y=20;
			addChild(titleBitmap);
		});
	}
	
	function userLogin(d){
		if(d.mid == mid){//我自己登录了
			wanjia.text = d.name;
			checkLayer.addChild(selfBitmap);
			checkLayer.addChild(wanjia);
			if(d.st == 2){
				wanjiazb.visible = true;
				wannozbBut.visible = false;
			}else{
				wanjiazb.visible = false;
				wannozbBut.visible = true;
				wannozbBut.removeEventListener(LMouseEvent.MOUSE_DOWN);
				wannozbBut.addEventListener(LMouseEvent.MOUSE_DOWN,wannozbButClick);
			}
			
		}else{//别人登录了
			diannao.text = d.name;
			checkLayer.addChild(foeBitmap);
			checkLayer.addChild(diannao);
			if(d.st == 2){
				dnzb.text = "已经准备";
			}else{
				dnzb.text = "没有准备";
			}
			dnzb.visible = true;
		}
		
	}
	
	
	
	function loginSucc(d){
		
	}
	
	function revZb(d){
		trace('准备：' + d.mid + ' ' + mid);
		if(d.mid == mid){//我自己准备了
			wannozbBut.visible = false;
			wanjiazb.visible = true;
		}else{
			dnzb.text = "已经准备";
		}
	}
	
	function gameReady(d){
		//backLayer.addChild(zbLayer);
		zbLayer.visible = true;
		wannozbBut.visible = true;
		wannozbBut.removeEventListener(LMouseEvent.MOUSE_DOWN);
		wannozbBut.addEventListener(LMouseEvent.MOUSE_DOWN,wannozbButClick);
		wanjiazb.visible = false;
		dnzb.text = "没有准备";
		
		msgText.visible = false;
	}
	
	function gameStart(d){
		//backLayer.removeChild(zbLayer);
		initControlLayer();
	}
	
	//游戏结束
	function gameOver(d){
		outRet(d);
		//d.other;
		trace('别人出拳：' + d.other);
		foeBitmap.bitmapData=showList[d.other-1];
		
		var msg = "平局";
		if(d.ret == -1){
			msg = "您输了";
		}else if(d.ret == 1){
			msg = "您赢了";
		}
		msgText.visible = true;
		msgText.text = msg;
	}
	
	function loadTable(d){
		trace('桌子ID:'+d.tid);
		loginLayer.visible = false;
		backLayer.visible = true;
		//backLayer.removeChild(loginLayer);
		initGameLayer();
		switch(d.gameSt){//游戏状态
			case 1://准备中
				zbLayer.visible = true;
				break;
			case 2://出拳中
				initControlLayer();
				break;
			case 3:
				msgText.visible = true;
				msgText.text =  "等待游戏开始。。";
				break;
		}
		
		outRet(d);
		
		if(!foeBitmap){
			foeBitmap=new LBitmap(showList[0]);
			foeBitmap.y =190-foeBitmap.getHeight();
			foeBitmap.x=270;
			diannao = initTextLayer('',"#fff",30,302,0);
		}
		
		if(!selfBitmap){
			selfBitmap=new LBitmap(showList[1]);
			selfBitmap.y =190-selfBitmap.getHeight();
			selfBitmap.x=40;
			wanjia = initTextLayer('',"#fff",30,68,0);
		}
	}
	
	//退出房间
	function exitSuc(d){
		if(d.mid == mid){//自己退出
			loginLayer.visible = true;
			backLayer.visible = false;
		}else{
			checkLayer.removeChild(foeBitmap);
			checkLayer.removeChild(diannao);
			dnzb.visible = false;
		}
	}
	
	function outRet(d){
		all = d.lose + d.win + d.draw;
		textField_draw.text="平局："+d.draw;
		textField_win.text="胜利次数："+d.win;
		textField_fail.text="失败次数："+d.lose;
		textField_All.text="总次数："+all;
	}
	
	//controlLayer代码快优化
	function initControlButton(z,h){
		var upBitmap = new LBitmap(showList[h]);
		upBitmap.scaleX=0.6;
		upBitmap.scaleY=0.6;
		var overBitMap = new LBitmap(showList[h]);
		overBitMap.scaleX=0.6;
		overBitMap.scaleY=0.6;
		overBitMap.x+=2;
		overBitMap.y+=2;
		var button = new LButton(upBitmap,overBitMap);
		button.x=z;
		button.y =35;
		button.addEventListener(LMouseEvent.MOUSE_DOWN,clickButton);
		button.index =h;
		return button;
	}
	
	
	function clickButton(event){
		name =event.clickTarget.index;//获取到被点击的button按钮的name属性的值
		var id = parseInt(name) + 1;
		var d = {};
		d.cmd = 0x104;
		d.data = {};
		d.data.guessId = id;
		mySocket.send(d);
	}
	
	function initControlLayer(){
		zbLayer.visible = false;
		var width_2=480,height_2=130;
		if(!controlLayer){
			var x_2=(LGlobal.width-width_2)/2,y_2=LGlobal.height-(height_2+20);
			controlLayer=new LSprite();
			controlLayer.x=x_2;
			controlLayer.y=y_2;
			
			controlLayer.graphics.drawRect(5,"AAFF00",[0,0,width_2,height_2],true,"#fff");
			//出拳标题
			var TextField=new initTextLayer("请出拳：","#000",15,20,5);
			controlLayer.addChild(TextField);
			//石头
			
			shitouButton=initControlButton(70,0);
			controlLayer.addChild(shitouButton);
			//剪刀
			jiandaoButton=initControlButton(190,1);
			controlLayer.addChild(jiandaoButton);
			//布
			buButton=initControlButton(320,2);
			controlLayer.addChild(buButton);
			
			backLayer.addChild(controlLayer);
		}
		//shitouButton.
		controlLayer.visible = true;
	}
	
	function initGameLayer(){
		//backLayer.graphics.drawRect(10,"green",[0,0,800,500],true,"black");//添加黑色背景
		
		backLayer.graphics.drawRect(10,"green",[0,0,800,80],true,"black");//添加黑色背景
		backLayer.graphics.drawRect(10,"green",[0,80,800,420],true,"#8B8989");//添加黑色背景
		trace('initGameLayer');
		if(!checkLayer){
			var width_3=400,height_3=200;
			var x_3=(LGlobal.width-width_3)/2,y_3=(LGlobal.height-height_3)/2-20;
			checkLayer=new LSprite();
			checkLayer.x=x_3;
			checkLayer.y=y_3;
		}
		backLayer.addChild(checkLayer);
		if(!zbLayer){
			zbLayer = new LSprite();
			var width_4=400,height_4=200;
			var x_4=(LGlobal.width-width_4)/2,y_4=LGlobal.height-height_4;
			zbLayer.x = x_4;
			zbLayer.y = y_4;
			wanjiazb = initTextLayer("已经准备","#00a600",20,68,20);
			dnzb = initTextLayer("已经准备","#00a600",20,302,20);
			//dnnozb = initTextLayer("没有准备","#00a600",20,302,20);
			
			wanjiazb.visible = false;
			dnzb.visible = false;
			
			wannozbBut = new LButtonSample1('点击准备');
			wannozbBut.backgroundColor = "#008800";
			wannozbBut.x = 68
			wannozbBut.y = 20;
			wannozbBut.visible = false;
			zbLayer.addChild(wanjiazb);
			zbLayer.addChild(dnzb);
			zbLayer.addChild(wannozbBut);
			backLayer.addChild(zbLayer);
		}
		
		initResultLayer();
		
		if(!exitBut){
			exitBut = new LButtonSample1('退出');
			exitBut.x = LGlobal.width - 80;
			exitBut.y = 20;
			exitBut.addEventListener(LMouseEvent.MOUSE_DOWN,
			function(evt){
				var d = {};
				d.cmd = 0X102;
				d.data = {};
				mySocket.send(d);
			});
			backLayer.addChild(exitBut);
		}
	
		
		if(!msgText){
			msgText = initTextLayer("","#B4EEB4",50,350,400);
			msgText.visible = false;
			backLayer.addChild(msgText);
		}
	}
	
	function wannozbButClick(evt){
		var d = {};
		d.cmd = 0X103;
		d.data = {};
		mySocket.send(d);
	}
	
	function initResultLayer(){
		if(!resultLayer){
			var width_1=150,height_1=160;
			var y_1= (LGlobal.height-height_1)/2;
			resultLayer=new LSprite();
			resultLayer.x=15;
			resultLayer.y=y_1;
			resultLayer.graphics.drawRect(5,"#AAFF00",[0,0,width_1,height_1],true,"#fff");
			
			//总次数
			textField_All=new initTextLayer("总次数：0","black",12,20,15);
			resultLayer.addChild(textField_All);
			//胜利次数
			textField_win=new initTextLayer("胜利次数：0","black",12,20,50);
			resultLayer.addChild(textField_win);
			//失败次数
			textField_fail=new initTextLayer("失败次数：0","black",12,20,85);
			resultLayer.addChild(textField_fail);
			//平局
			textField_draw=new initTextLayer("平局：0","black",12,20,120);
			resultLayer.addChild(textField_draw);
			backLayer.addChild(resultLayer);
		}
	}
	
	return {
		start:function(ar){
			mySocket.address = ar;
			init(200,"myGame",800,500,main);
		},
		recv:function(d){
			console.log('接收命令',d);
			switch(d.cmd){
				case 0x201://登录成功
					loginSucc(d.data);
					break;
				case 0x202://退出成功
					//exitSuc(d.data);
					break;
				case 0x204:
					//已经出拳
					if(d.data.ret){
						selfBitmap.bitmapData=showList[d.data.guessId-1];
						//backLayer.removeChild(controlLayer);
						controlLayer.visible = false;
					}
					break;
				case 0x300://下发房间数据
					loadTable(d.data);
					break;
				case 0x301://开始准备
					gameReady(d.data);
					break;
				case 0x302:
					gameStart(d.data);
					break;
				case 0x303://游戏结算
					gameOver(d.data);
					break;
				case 0x403:
					revZb(d.data);
					break;
				case 0x401://有人登录了
					userLogin(d.data);
					break;
				case 0x402://广播退出
					exitSuc(d.data);
					break;
			}
		},
		exit:function(){
			exitSuc({mid:mid});
		}
	};
};


var LButtonSample1 = (function() {
    function LButtonSample1(name, size, font, color) {
        var s = this;
        if (!size) {
            size = 16;
        }
        if (!color) {
            color = "#FFFFFF";
        }
        if (!font) {
            font = "Arial";
        }
        s.backgroundColor = "#000000";
        var btn_up = new LSprite();
        btn_up.shadow = new LSprite();
        btn_up.back = new LSprite();
        btn_up.addChild(btn_up.shadow);
        btn_up.addChild(btn_up.back);
        labelText = new LTextField();
        labelText.color = color;
        labelText.font = font;
        labelText.size = size;
        labelText.x = size * 0.5;
        labelText.y = size * 0.5;
        labelText.text = name;
        s.labelText = labelText;
        btn_up.back.addChild(labelText);
        var shadow = new LDropShadowFilter(4,45,"#000000",10);
        btn_up.shadow.filters = [shadow];
        var btn_down = new LSprite();
        btn_down.x = btn_down.y = 1;
        labelText = labelText.clone();
        btn_down.addChild(labelText);
        var btn_disable = btn_down.clone();
        btn_disable.alpha = 0.5;
        LExtends(s, LButton, [btn_up, btn_down, null , btn_disable]);
        s.type = "LButtonSample1";
        s.baseWidth = s.width = labelText.getWidth() + size;
        s.baseHeight = s.height = 2.2 * size;
        s.backgroundSet = null ;
        s.widthSet = null ;
        s.heightSet = null ;
        s.xSet = null ;
        s.ySet = null ;
        s.addEventListener(LEvent.ENTER_FRAME, s._onDraw);
    }
    LButtonSample1.prototype.clone = function() {
        var s = this
          , name = s.labelText.text
          , size = s.labelText.size
          , font = s.labelText.font
          , color = s.labelText.color
          , a = new LButtonSample1(name,size,font,color);
        a.backgroundColor = s.backgroundColor;
        a.x = s.x;
        a.y = s.y;
        return a;
    }
    ;
    LButtonSample1.prototype.setLabel = function(text) {
        var s = this;
        s.bitmap_over.getChildAt(0).text = s.bitmap_up.back.getChildAt(0).text = text;
        s.baseWidth = s.width = s.bitmap_up.back.getChildAt(0).getWidth() + s.bitmap_up.back.getChildAt(0).size;
        s.backgroundSet = null ;
    }
    ;
    LButtonSample1.prototype._onDraw = function(s) {
        var co = s.getRootCoordinate(), labelText;
        if (s.backgroundSet == s.backgroundColor && s.widthSet == s.width && s.heightSet == s.height && s.xSet == co.x && s.ySet == co.y) {
            return;
        }
        s.backgroundSet = s.backgroundColor;
        s.widthSet = s.width > s.baseWidth ? s.width : s.baseWidth;
        s.heightSet = s.height > s.baseHeight ? s.height : s.baseHeight;
        s.width = s.widthSet;
        s.height = s.heightSet;
        s.xSet = co.x;
        s.ySet = co.y;
        labelText = s.bitmap_up.back.getChildAt(0);
        labelText.x = (s.width - s.baseWidth + labelText.size) * 0.5;
        labelText.y = (s.height - s.baseHeight + labelText.size) * 0.5;
        labelText = s.bitmap_over.getChildAt(0);
        labelText.x = (s.width - s.baseWidth + labelText.size) * 0.5;
        labelText.y = (s.height - s.baseHeight + labelText.size) * 0.5;
        var grd = LGlobal.canvas.createLinearGradient(0, -s.height * 0.5, 0, s.height * 2);
        grd.addColorStop(0, "#FFFFFF");
        grd.addColorStop(1, s.backgroundColor);
        var grd2 = LGlobal.canvas.createLinearGradient(0, -s.height, 0, s.height * 2);
        grd2.addColorStop(0, "#FFFFFF");
        grd2.addColorStop(1, s.backgroundColor);
        s.bitmap_up.back.graphics.clear();
        s.bitmap_over.graphics.clear();
        s.bitmap_up.shadow.graphics.clear();
        s.bitmap_up.shadow.graphics.drawRoundRect(0, "#000000", [1, 1, s.widthSet - 2, s.heightSet - 2, s.heightSet * 0.1], true, "#000000");
        s.bitmap_up.back.graphics.drawRect(1, s.backgroundColor, [0, 0, s.widthSet, s.heightSet], true, grd);
        s.bitmap_up.back.graphics.drawRect(0, s.backgroundColor, [1, s.heightSet * 0.5, s.widthSet - 2, s.heightSet * 0.5 - 1], true, grd2);
        s.bitmap_over.graphics.drawRect(1, s.backgroundColor, [0, 0, s.widthSet, s.heightSet], true, grd);
        s.bitmap_over.graphics.drawRect(0, s.backgroundColor, [1, s.heightSet * 0.5, s.widthSet - 2, s.heightSet * 0.5 - 1], true, grd2);
        s.disableState.graphics.drawRect(1, s.backgroundColor, [0, 0, s.widthSet, s.heightSet], true, grd);
        s.disableState.graphics.drawRect(0, s.backgroundColor, [1, s.heightSet * 0.5, s.widthSet - 2, s.heightSet * 0.5 - 1], true, grd2);
    }
    ;
    return LButtonSample1;
})();