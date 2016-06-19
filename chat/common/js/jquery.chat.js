/*
 * jQuery CHAT v.1.00
 * 
 * Copyright(C)2014 STUDIO KEY Allright reserved.
 * http://studio-key.com
 * MIT License
 */


/*
 * loading
 */
jQuery.event.add(window,"load",function() { 
    jQuery("#fade").css("height", '100%').delay(900).fadeOut(800);
    jQuery("#loader").delay(600).fadeOut(300);
    jQuery("#container").css("display", "block");
});



var type = (function(){
  if (!location.href.split('?')[1]) return null;
  params = location.href.split('?')[1].split('&')
  for (var i = 0; i < params.length; i ++) {
    var key = params[i].split('=')[0];
    var val = params[i].split('=')[1];
    if (key == 'type') return val;
  }
  return null;
})();

(function($){
  $.fn.jqueryChat = function(options) {
    var opt = $.extend( {
        'reload'     : 10000,
        'readlog'    : true,
        'delcookie'  : false,
        'log_login'  : true,
        'log_logof'  : true,
        'bt_name'    : 'お名前を送信',
        'bt_chat'    : '書き込む',
        'mes_logout' : '退室しますか？',
        'err_name'   : 'お名前を書いて下さい',
        'err_upload' : 'アップロードに失敗しました',
        'err_write'  : 'コメントが未記入です',
        'err_wnig'   : '不明なエラーが発生しました',
        'login'      : 'さんが入室しました',
        'logout'     : 'さんが退室しました',
        'cmdmes'     : 'コマンドが違います',
        'flaggmap'   : false,
        'gmap'       : '送信すると貴方の位置情報が相手に知られます。宜しいですか？',
        'color'      : 'FF0004,0A00FF,FF00D6,2B9700,FF7600,666666'
    }, options);

/*
 * 名前と個人判別キーをルーム固有にする
 * jquery_chat_name
 * jquery_chat_unique
 */
    var jquery_chat_name   = 'jquery_chat_name'+opt.room_id;
    var jquery_chat_unique = 'jquery_chat_unique'+opt.room_id;
    
    

/*****************************************************
 * 初回ロード時にDB接続を確認する
 *****************************************************/
    $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=db_check&room="+opt.room_id,
        success: function(xml){
          
          var error = $(xml).find("error").text();
          if(error){
            $("#log").html('<span style="color:red;font-size:90%">データベースエラー<br />'+error+'</span>');
          }
        }
    });


/*****************************************************
 * HTMLなんかを挿入する
 *****************************************************/


  /* システム設定拡張用
   *------------------------------------*/
      var html  = '';
          html += '  <div id="sys_setting" class="pc">';
          html += '    <div id="sys_row">';
          html += '      <div id="sys_th">過去ログ</div>';
          html += '      <div id="sys_td">';
          html += '        <select id="log_len">';
          html += '          <option value="100">100</option>';
          html += '          <option value="150">150</option>';
          html += '          <option value="200">200</option>';
          html += '          <option value="250">250</option>';
          html += '          <option value="300">300</option>';
          html += '          <option value="350">350</option>';
          html += '          <option value="400">400</option>';
          html += '          <option value="450">450</option>';
          html += '          <option value="500">500</option>';
          html += '        </select>';
          html += '      </div>';
          html += '    </div>';
          html += '  </div>';
          $(this).after(html);


  /* カラーパレット
   *------------------------------------*/
      var color = opt.color.split(",");
      var html  = '';
          html += '<div id="setting" class="pc">';
          html += '<div>';
          html += '<p>Name</p>';
          for(var i=0; i<color.length; i++){
            html += '<span style="color:#'+color[i]+'" class="n" title="'+color[i]+'">■</span>';
          }
          html += '</div>';
          html += '<div>';
          html += '<p>Log</p>';
          for(var i=0; i<color.length; i++){
            html += '<span style="color:#'+color[i]+'" class="l" title="'+color[i]+'">■</span>';
          }
          html += '</div>';
          html += '</div>';
          $(this).after(html);
          
  /* すたんぷ
   *------------------------------------*/
          $(this).after('<div id="stump_wrap" class="pc"></div>'); 
          
  /* message
   *------------------------------------*/
          $(this).after('<div id="message" class="pc"></div>');

  /* camera
   *------------------------------------*/
      var html = '';
          html +='<div id="camera" class="pc">';
          html +='  <div id="per"></div>';
          html +='  <div class="pbar" id="pbar" style="background: red"></div>';
          html +='  <form id="upform" name="upform">';
          html +='    <input type="file" name="files_field" id="files_field">';
          html +='    <input id="fupload" type="button" value="UPLOAD" ';
          html +='  </form>';
          html +='</div>';
          $(this).after(html);

  /* ログ移動位置
   *------------------------------------*/
          $(this).after('<div id="end"></div>');

  /* 送信フォーム
   *------------------------------------*/
      var html  = '';
          html += '<div id="chat" class="pc">';
          html += '  <form id="form" name="form">';
          html += '    <input name="c" id="c"  type="hidden" value="">';
          html += '    <input name="l" id="l"  type="hidden" value="">';
          html += '    <input name="var" type="text" id="var" maxlength="8">';
          html += '    <a href="#" id="button"></a>';

          html += '    <div id="camera_icon"><img src="" width="35" height="35" border="0"/></div>';
          
          html += '  </form>';
          html += '</div>';
          $(this).after(html);
          
  /* スタンプの読み込み
   *------------------------------------*/
    $("#stump_wrap").html('');
          $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=stump",
              success: function(xml){
                $(xml).find("item").each(function(){
                  var stp = $(this).find("stp").text();
                  $('#stump_wrap').append('<img src="stump/thumbnail/'+stp+'" id="'+stp+'" class="stmp" />');
                });
              }
          });
          
/*****************************************************
 * 設定で表示・非表示を変更
 *****************************************************/
  /* Gmap機能
   *------------------------------------*/
    if(opt.flaggmap == true){
			if (!navigator.geolocation) {
				$("#map").hide();
			}
    }else{
      $("#map").hide();
    }
  /* 初回ログ表示
   *------------------------------------*/
    if( opt.readlog === true ){
      readLog(false,'');
    }
  /* 初回にcookieを消すか
   *------------------------------------*/
    if( opt.delcookie === true ){
      $.removeCookie(jquery_chat_name);
      $.removeCookie(jquery_chat_unique);
    }
    
    
/*****************************************************
 * HTMLの挿入が終わったら
 * ウインドサイズによって幅を変更する
 *****************************************************/
      width_change($(window).width());
      $(window).resize(function(){
        width_change($(window).width());
      });

  /* 画面を最下部へ移動 
   * ロード時は少し遅延させ、ゆったり移動
   *------------------------------------*/
      setTimeout(function(){
           var pos = $("#end").offset().top; 
           $("html, body").animate({ 
              scrollTop:pos 
           }, 1000, "swing");
      },3000);


  /* COOKIEに名前があるかどうか
   *------------------------------------*/
      var name = $.cookie(jquery_chat_name);
      if(!name){
        $("#form a").text(opt.bt_name);
      }else{
        $("#form a").text(opt.bt_chat);
        $("#var").attr("maxlength","500");
      }
      
      
/*****************************************************
 * システム設定色々 len_change_button log_len
 * @len_change_button -> 表示件数の変更(過去ログ閲覧) 
 *****************************************************/
      $(document).on('change','#log_len',function(e){
        var len = $("#log_len").val();
        
        $("#log").data("slide",'off'); //スライド機能をオフ
        readLog('',len); //全ログ書き変えで指定件数を表示 *下段には追加されていくが、下段へスライドはしない 次の発言でスライドON
        
        $("#sys_setting").hide();
        $("#header").hide();
        e.preventDefault();
      });
      
      

/*****************************************************
 * logout
 *****************************************************/
      $(document).on('click','#logof',function(e){
        if($.cookie(jquery_chat_name)){
          var logof = confirm(opt.mes_logout);
          if(logof == true){
            logWrite(null,true);
          }
        }
        e.preventDefault();
      });
      
      
      
/*****************************************************
 * 現在位置情報
 *****************************************************/

  /* クリックで現在地を取得
   *------------------------------------*/
      $(document).on('click','#map img',function(e){
        if($.cookie(jquery_chat_name)){
          var conf = confirm(opt.gmap);
          if(conf == true){
            navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
          }
        }
        e.preventDefault();
      });
      

  /* [ログ記録処理] Geolocation API
   *------------------------------------*/
        function successCallback(position) {
          var lon = position.coords.latitude;
          var lat = position.coords.longitude;
          
          if(lon && lat){
            var val = lon+','+lat;
              $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=gmap&room="+opt.room_id+"&val="+val,
                  success: function(xml){
                    readLog(true,'');
                    cs_top();
                  }
              });
          }
          
        }
        function errorCallback(error) {
          switch(error.code) {
            case 1:
              $("#message").slideDown(200).text('位置情報の取得が許可されませんでした');
              break;
            case 2:
              $("#message").slideDown(200).text('位置情報の取得に失敗しました');
              break;
            case 3:
              $("#message").slideDown(200).text('タイムアウトしました');
              break;
          }
        }

/*****************************************************
 * セッティングボックスの開閉 
 *****************************************************/
      $("#system").click(function (e) {
        $("#stump_wrap").slideUp();
        $("#setting").slideUp();
        $("#sys_setting").hide();
        
        if($.cookie(jquery_chat_name)){
          $("#header").slideToggle();
        }
        e.preventDefault();
      });
      
      
/*****************************************************
 * セッティング色々ぼっくす
 *****************************************************/
      $("#setup").click(function (e) {
        $("#stump_wrap").slideUp();
        $("#setting").slideUp();
        
        if($.cookie(jquery_chat_name)){
          $("#sys_setting").slideToggle();
        }
        
        e.preventDefault();
      });

/*****************************************************
 * カラーカスタマイズ
 *****************************************************/
      $("#custom").click(function (e) {
        $("#stump_wrap").hide();
        $("#sys_setting").hide();
        
        if($.cookie(jquery_chat_name)){
          $("#setting").slideToggle();
        }
        e.preventDefault();
      });

      $(document).on('click','.n',function(e){
        $("#c").val( $(this).attr('title') );
        $("#setting").slideUp();
        $("#header").slideUp();
        e.preventDefault();
      });
      $(document).on('click','.l',function(e){
        $("#l").val( $(this).attr('title') );
        $("#setting").slideUp();
        $("#header").slideUp();
        e.preventDefault();
      });

/*****************************************************
 * スタンプボックス
 *****************************************************/
      $("#stump").click(function (e) {
        $("#setting").hide();
        $("#sys_setting").hide();
        if($.cookie(jquery_chat_name)){
          $("#stump_wrap").slideToggle();
        }
        e.preventDefault();
      });

  /* [ログ記録処理] スタンプ送信
   *------------------------------------*/
      $(document).on('click','.stmp',function(e){
        var img = $(this).attr("id");
        $("#stump_wrap").slideUp();
        $("#header").slideUp();
        
          $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=gostump&room="+opt.room_id+"&stump="+img,
              success: function(xml){
                var limit = $(xml).find("limit").text();
                if(!limit){
                  readLog(true,'');
                  cs_top();
                }else{
                  $("#message").slideDown(200).text(limit);
                }
              }
          });
          
      });


/*****************************************************
 * カメラ
 * 入室していない場合は開かない
 *****************************************************/
      $("#camera_icon img").click(function (e) {
        $("#message").hide();
        if($.cookie(jquery_chat_name)){
          $("#camera").slideToggle();
          e.preventDefault();
        }
      });

/*****************************************************
 * 書いてないよー等のメッセージ部分はクリックで消す
 *****************************************************/

      $(document).on('click','#message',function(e){
        $(this).slideUp();
      });


/*****************************************************
 * ボタン送信!!
 *****************************************************/
      $(document).on('click','#button',function(e){
        clearTimeout(timer_id);
        $("#message").hide();
        var val = $("#var").val();
            val = $.trim(val); //空白だけはダメ
            val = sanitize( val ); //送信された値をサニタイズ

  /* 入室時 (名前送信) 
   *------------------------------------*/
        if($(this).text() === opt.bt_name){

          if( val ){
            $.cookie(jquery_chat_name, val, { expires: 7 }); //名前をcookieに記録
            $("#var").val(''); //inputを空に
            $("#form a").text(opt.bt_chat); //ボタンを変更
            $("#var").attr("maxlength","500"); //maxlengthを変更
            if(opt.log_login === true) logWrite(val,null);
          }else{
            $("#var").val('');
            $("#message").slideDown(200).text(opt.err_name);//空の場合はエラー
          }

        }
  /* チャット送信!!
   *------------------------------------*/
        else{
          if( val ){
            if( $.cookie(jquery_chat_name) ){
              logWrite(null,null);
            }else{
              $("#message").slideDown(200).text(opt.err_wnig); //入室しているのにログを空にした場合など
              setTimeout(function(){
                location.href=location.href;
              },3000);
            }

          }else{
            $("#var").val('');
            // $("#message").slideDown(200).text(opt.err_write);//空の場合はエラー
          }
        }
        e.preventDefault();
      });





/*****************************************************
 * ログを書き込む
 *****************************************************/
    function logWrite(name,logout){
      
    /* [ログ記録処理]
     * 名前送信の場合は入室メッセージ
     *------------------------------------*/
      if(name){

       //個人を特定するユニークな値をCOOKIEに記録
          var unique = Math.round( Math.random()*10000 )+'_'+$.now();
          $.cookie(jquery_chat_unique, unique, { expires: 7 });

          $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=login&room="+opt.room_id+"&mes="+opt.login+"&str="+name,
              success: function(xml){
                readLog(true,'');
              }
          });

      }

    /* [ログ記録処理]
     * 退室ならば
     *------------------------------------*/
      else if(logout){
        if(opt.log_logof === true) {
          $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=logout&room="+opt.room_id+"&name="+$.cookie(jquery_chat_name)+"&mes="+opt.logout,
              success: function(xml){
                readLog(true,'');
                cs_top();
                $.removeCookie(jquery_chat_name);
                $.removeCookie(jquery_chat_unique);
                location.href=location.href;
              }
          });
        }else{
            $.removeCookie(jquery_chat_name);
            $.removeCookie(jquery_chat_unique);
            location.href=location.href;
        }
      }

    /* 
     * チャット
     *------------------------------------*/
      else{
          var val    = $("#var").val();
          $("#log").data("slide",'on'); //発言をしたらスライドをON

       //コマンド処理
       //=========================================
          //テーマを変える 
            if(val === 'てーま'){
              Theme();
              $("#var").val('');
              return ;
            }
            
            if(val === 'せつめい'){
              var str  = '<li class="li3" id="setumei">';
                  str += '[てーま]<br />背景色がランダムで変更します<br />';
                  str += '[おみくじ]<br />おみくじが引けます<br />';
                  str += '[けんこう]<br />健康運を占います<br />';
                  str += '[れんあい]<br />恋愛運を占います<br />';
                  str += '</li>';
                  $('#log ul').append( str );
                  cs_top();
                  $("#var").val('');
                  return ;
            }
            var kuzi = '';
            if(val === 'おみくじ' || val === 'けんこう' || val === 'れんあい'){
              kuzi = val;
            }
            
          $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=send&room="+opt.room_id+"&str="+val+'&c='+$("#c").val()+'&l='+$("#l").val()+"&kuzi="+kuzi+"&type="+type,
              success: function(xml){
                var limit = $(xml).find("limit").text();
                if(!limit){
                  readLog(true,'');
                  cs_top();
                }else{
                  $("#message").slideDown(200).text(limit);
                }
                
              }
          });
          $("#var").val(''); //inputを空に
      }
    }



/*****************************************************
 * ログ配列
 *****************************************************/
    function logRow(xml){
       var cls  = $(xml).find("cls").text();
       var name = $(xml).find("name").text();
       var log  = $(xml).find("log").html();
       var date = $(xml).find("date").text();
       var col1 = $(xml).find("col1").text();
       var col2 = $(xml).find("col2").text();
       var img  = $(xml).find("img").text();
       var hash = $(xml).find("hash").text();
       
       log = sanitize( log ); 
       
       log = getLink(log);
       var dstyle = '';
         if(cls === 'li3'){
           return html = '<li class="li3" id="'+hash+'">'+name+':'+log+'</li>';
         }
         else if(cls === 'li4'){
           return html = '<li class="li4" id="'+hash+'">'+log+'</li>';
         }
         else{
           
             if(cls == 'li1'){
               balloon = 'left_balloon'

             }else{
               balloon = 'right_balloon'
               dstyle = ' style="text-align:right"';
             }
           
           if(img === 'IMG'){
             var log = '<a href="upload/o_'+log+'" target="_blank"><img src="upload/t_'+log+'" /></a>';
             return html = '<li class="'+cls+'" id="'+hash+'"><p class="name" style="color:'+col1+'">'+name+'</p><p class="log" style="color:'+col2+'">'+log+'<span'+dstyle+'>'+date+'</span></p></li>';
           }
           else if(img === 'STUMP'){
             var log = '<img src="stump/img/'+log+'" />';
             
             if(cls == 'li1'){
               cls = 'li5';
             }else{
               cls = 'li6';
               
             }
             return html = '<li class="'+cls+'" id="'+hash+'"><p class="name" style="color:'+col1+'">'+name+'</p><p class="log" style="color:'+col2+'">'+log+'<span'+dstyle+'>'+date+'</span></p></li>';
           }
           else if(img === 'GMAP'){
             var log = '<a href="gmap.php?map='+log+'" target="_blank">イマココ</a>';
             return html = '<li class="'+cls+'" id="'+hash+'"><p class="name" style="color:'+col1+'">'+name+'</p><p class="log" style="color:'+col2+'">'+log+'<span'+dstyle+'>'+date+'</span></p></li>';
           }
           else{
             log = log.replace(/\r?\n/g, '<br>');
             html = '<li class="'+cls+'" id="'+hash+'"><p class="name" style="color:'+col1+'">'+name+
             '</p><p class="log '+balloon+'"style="color:'+col2+'">'+log+'</p></li>';
             return html
           }
         }
    }


/*****************************************************
 * ログの書き変えと追加
 *****************************************************/
    function readLog(append,len){
      if(!len) len = '';

      if(append == true){
        append = 1;
      }else{
        append = '';
      }
      
    /* 
     * 全ログ書き変えの場合だけ全削除
     *------------------------------------*/
      if(append == ''){
        $('#log ul li').remove();
      }
      
      
      
      var lasthash = $("#log").data('lasthash'); //自分が最後に見たLIのID

      $.ajax({ type: "POST",async:false,url: "common/php/chat.php",data: "mode=readLog&room="+opt.room_id+"&append="+append+"&lasthash="+lasthash+"&len="+len,
          success: function(xml){
            if( $(xml).find("item").length > 0 ){
               $(xml).find("item").each(function(){ 
                 $('#log ul').append( logRow($(this)) );
               });
            }
             

           //入室していない場合、ログはリロードしない
             if( $.cookie(jquery_chat_name) ) {
                 log_reload();
             }
          }
          
      });
      
      
    /* 
     * LI 最後尾のIDを得る
     * このID以降のログを取得することで
     * 最新ログだけを追加していく
     *------------------------------------*/
      var id=null;
      $("#log li").each(function(){
        id= $(this).attr("id");
      });
      $("#log").data('lasthash',id);
      
      
    }


/*****************************************************
 * ログのリロード
 *****************************************************/
    var timer_id;
    function log_reload(){
      clearTimeout(timer_id); //setTimeoutは初期化する
//      
      var slide = $("#log").data("slide");

    /* 
     * 最新記事を確認
     *------------------------------------*/
      $.ajax({ type: "POST",async:false,url: "common/php/chat.php",data: "mode=reload&room="+opt.room_id,
          success: function(xml){
            var flag  = $(xml).find("flag").text()|0;
            if(flag === 1) {
              readLog(true,'');
              if(slide === 'on') cs_top();
            }
          }
      });
      
    /* 
     * 指定時間で繰り返し処理
     *------------------------------------*/
      timer_id = setTimeout(function(){
        log_reload();
      },opt.reload);

    }


/*****************************************************
 * エンターキー制御
 * 入室の時は間違ってエンター押すと面倒なので
 * エンターで書き込むのはチャット送信時だけにする
 *****************************************************/
      $("#form").keypress(function(ev) {
        if ((ev.which && ev.which === 13) || (ev.keyCode && ev.keyCode === 13)) {
          $("#message").hide();
          if($.cookie(jquery_chat_name)) logWrite(null,null); 
          return false;
        }
      });



/*****************************************************
 * URLぽい文字にリンクタグを
 * http://keicode.com/script/scr18.php
 * メアドは顔文字等で問題が出そうなのでパス
 *****************************************************/
    function getLink(s){
      if(!s){
        return '';
      }
      var re_url = /((ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?)/gi;
      //var re_mail = /((?:\w+\.?)*\w+@(?:\w+\.)+\w+)/gi;
      s = s.replace( re_url, '<a href="$1" target="_blank">$1</a>' );
      //s = s.replace( re_mail, '<a href="mailto:$1">$1</a>');
      return s;
    }


/*****************************************************
 * 幅を100%にする
 *****************************************************/
    function width_change(w){
      var camera = $("#container").width()-45;
      $("#form a").css({"width":camera+'px'});
      $(".pc").css({"width":'100%'});
    }


/*****************************************************
 * 下へスライドする
 *****************************************************/
    function cs_top(){
         var pos = $("#end").offset().top; 
         $("html, body").animate({ 
            scrollTop:pos 
         }, 0, "swing");
    }
    
    

/*****************************************************
 * ファイルアップロード
 *****************************************************/
      $(document).on('click','#fupload',function(e){
        start_upload();
      });
      var start_upload = function(){
          var FormId = $("#upform");  
          var fd     = new FormData(FormId[0]);

          jQuery.ajax({
              async: true,
              xhr : function(){
                  var XHR = $.ajaxSettings.xhr();
                  if(XHR.upload){
                      XHR.upload.addEventListener('progress',function(e){
                          progre = parseInt(e.loaded/e.total*10000)/100 ;
                          $("#pbar").width(parseInt(progre/100*300*100)/100+"px");
                          $("#pbar").height("5px");
                          $("#per").html(progre+"%");
                      }, false); 
                  }
                  return XHR;
              },
              url:  "common/php/upload.php",
              type: "post",
              data:fd,
              contentType: false,
              processData: false

          }).done(function( xml ) { 
            var file = $(xml).find("file").text();
            var flag = $(xml).find("flag").text();

            if(flag != 1){
              // $("#message").slideDown(200).text(opt.err_upload);//空の場合はエラー
            }else{
              $("#per").html("100.00%");
              
    /* 
     * [ログ記録処理]
     * 画像をログに記録
     *------------------------------------*/
              $.ajax({ type: "POST",url: "common/php/chat.php",data: "mode=file&room="+opt.room_id+"&file="+file,
                  success: function(xml){
                    readLog(true,'');
                    cs_top();
                  }
              });
            }
          });

          setTimeout(function(){
            $("#camera").slideUp();
            $("#pbar").height("0px");
            $("#per").html("");
          },3000);

          $("#files_field").val('');
      };

  };

  
  /* 
   * サニタイズ
   *------------------------------------*/
    function sanitize(val){
      return $('<div />').text(val).html();
    }
    

  /* 
   * Themeの設定と変更
   * テーマは全体の背景色と背景画像を指定する事が出来ます。
   * 数を増やした場合はその分だけ以下の設定を変更して下さい。
   * var n = Math.ceil( Math.random()*7 );
   *----------------------------------------------------------------------*/
    function Theme(){
      var n = Math.ceil( Math.random()*7 ); //0~7までの整数をランダムで生成
      
      var Theme = new Array;
      Theme[0] = {'bgcolor':'#BCFFCE' , 'bgimage':'none' };
      Theme[1] = {'bgcolor':'#ffc0cb' , 'bgimage':'none' };
      Theme[2] = {'bgcolor':'#98fb98' , 'bgimage':'none' };
      Theme[3] = {'bgcolor':'#db7093' , 'bgimage':'none' };
      Theme[4] = {'bgcolor':'#a9a9a9' , 'bgimage':'none' };
      Theme[5] = {'bgcolor':'#e6e6fa' , 'bgimage':'none' };
      Theme[6] = {'bgcolor':'#bdb76b' , 'bgimage':'none' };
      Theme[7] = {'bgcolor':'#778899' , 'bgimage':'url(thema_img/01.png)' };

      var set_Theme = Theme[n];
      if(!set_Theme) set_Theme = Theme[0];

      if(set_Theme.bgcolor){
        $("body").css({"background-color":set_Theme.bgcolor});
        $("#container").css({"background-color":set_Theme.bgcolor});
      }

      if(set_Theme.bgimage){
        $("#container").css({"background-image":set_Theme.bgimage});
      }
      
    }
  
  
})(jQuery);



