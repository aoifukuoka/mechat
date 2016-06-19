<?php
/*
 * jQuery CHAT v.1.00
 * 
 * Copyright(C)2014 STUDIO KEY Allright reserved.
 * http://studio-key.com
 * MIT License
 */


mb_language("Japanese");
mb_internal_encoding("UTF-8");
session_start();
error_reporting(0);


/*
 * 管理人の名前
 */
define('ADMIN_NAME','管理人');

/*
 * SQLiteの場所
 * 相対パスで書かれていますが、出来るだけWEB公開ディレクトリを避けて設置し
 * 絶対パスで指定するなどセキュリティに配慮して下さい。
 */
define('SQLITE','../../db/chat.sqlite');

/*
 * 発言制限(秒)
 * ここで指定した秒数の間は次の発言が出来ません。
 */
define('write_limit',0); //0で無制限


/*
 * 初回に読み込むログ数
 * 過去ログ機能が有りますので、あまり多くせず30～50が良いと思います。
 */
define('LEN',30);

// 設定ここまで -------------------------------------------------------------------------------
require_once('omikuzi.php'); //おみくじ設定ファイルを読み込む *内容を変更したい場合は参照して下さい

/******************************************
 * 変数定義とサニタイズ
 ******************************************/
/*
 * POSTされている＆配列であるか確認
 */
if(isset($_POST) AND is_array($_POST)){
  foreach($_POST AS $key=>$str){
    $post[$key] = htmlspecialchars($str , ENT_QUOTES , "UTF-8");
  }
}else{
  return; //POSTされていなければ停止
}

/*
 * IPと送信日時を足してmd5でハッシュにして発言ごとのユニークを作る
 */
  $hash = md5($_SERVER["REMOTE_ADDR"].time());
  
/*
 * 発言色を定義
 */
  if($post['c']){
    $name_color = '#'.$post['c'];
  }else{
    $name_color = '#000000';
  }
  if($post['l']){
    $log_color = '#'.$post['l'];
  }else{
    $log_color = '#000000';
  }

  
/******************************************
 * modeで処理を分岐
 ******************************************/
  switch($post['mode']){
    case 'db_check':
      $db_error = null;
      $db = dbClass::connect();
      if($db === 'error'){
        $db_error = 'データベースに接続出来ません';
      }else{
        try { 
          $stmt = $db->prepare("SELECT * FROM chat_log"); 
          $stmt->execute();
          
          $stmt = $db->prepare("SELECT COUNT(*) AS count FROM chat_log WHERE room_id=:room_id "); //この部屋のログ数を確認
          $stmt->execute(array(':room_id'=>$post['room']));
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          $row['count'] = $row['count']*1;

        } catch (PDOException $err) { 
          $db_error = 'データベーステーブルが不正です';
        }
      } 

      if($db_error !== null){

       header("Content-type: application/xml");
       echo '<?xml version="1.0" encoding="UTF-8" ?> ' . "\n";

       echo '<xml>'."\n"; 
       echo '      <error>'.$db_error.'</error>'."\n"; 
       echo '</xml>'."\n"; 
       exit;
       
      }else{
        
        /*
         * データベース接続とテーブルの確認を終えたら、この部屋のログ数を確認
         * ログがゼロの場合は初期設定OKのログをインサート
         * この処理を行わないと、非同期→リロードが無限ループに陥る可能性有
         * *log_write()は使わない
         */
          if($row['count'] < 1){
              $data = array(
                   ':room_id'      => $post['room']
                  ,':chat_unique'  => 'ADMIN'
                  ,':hash'         => $hash
                  ,':time'         => time()
                  ,':chat_name'    => 'システム'
                  ,':str'          => 'チャットルームが有効になりました'
                  ,':log_sort'     => 'li3'
                  ,':created'      => date('Y-m-d')
              );
              
              $sql = "INSERT INTO 'chat_log' ( 'id' , 'room_id' , 'chat_unique' , 'hash' , 'time' , 'chat_name' , 'str' ,'log_sort' , 'created' )
              VALUES ( NULL , :room_id , :chat_unique , :hash , :time , :chat_name , :str ,:log_sort , :created ) ";
              try { 
                $stmt = $db->prepare($sql);
                $stmt->execute($data);
              } catch (PDOException $err) {
                //return $err->getMessage();
              }
          }
      }
    break;
    
/*
 * ログイン
 */
    case 'login':
      
      $log_str = $post['str'].$post['mes'];
      $data = array(
           ':room_id'      => $post['room']
          ,':chat_unique'  => 'ADMIN'
          ,':hash'         => $hash
          ,':time'         => time()
          ,':chat_name'    => ADMIN_NAME
          ,':str'          => $log_str
          ,':remoote_addr' => $_SERVER["REMOTE_ADDR"]
          ,':name_color'   => $name_color
          ,':log_color'    => $log_color
          ,':chat_type'    => ''
          ,':created'      => date('Y-m-d')
      );
      
        log_write($data,$post['room']);
    break;
/*
 * 発言
 */
    case 'send':
    //発言制限の時間
      if(!$_SESSION['write_limit']) $_SESSION['write_limit'] = time();
      if(time() < $_SESSION['write_limit']){
        write_stop();
        return ;
      }
      $_SESSION['write_limit'] = mktime(date('H'),date('i'),date('s')+write_limit,date('m'),date('d'),date('Y'));
      
      $log_str = $post['str'];
      $data = array(
           ':room_id'      => $post['room']
          ,':chat_unique'  => $_COOKIE['jquery_chat_unique'.$post['room']]
          ,':hash'         => $hash
          ,':time'         => time()
          ,':chat_name'    => $_COOKIE['jquery_chat_name'.$post['room']]
          ,':str'          => $log_str
          ,':remoote_addr' => $_SERVER["REMOTE_ADDR"]
          ,':name_color'   => $name_color
          ,':log_color'    => $log_color
          ,':chat_type'    => ''
          ,':created'      => date('Y-m-d')
      );
      
      
    /*
     * おみくじ発動！
     */
      if($post['kuzi']){
        $data['kuzi'] = 'kuzi';
        $omikuzi = new Omikuzu;
        
        switch($log_str){
          case 'おみくじ':
            $data[':str']  = '['.$_COOKIE['jquery_chat_name'.$post['room']].'さん] ';
            $data[':str'] .= $omikuzi->Nomal();
          break;
          case 'けんこう':
            $data[':str']  = '['.$_COOKIE['jquery_chat_name'.$post['room']].'さん] ';
            $data[':str'] .= $omikuzi->Kenko();
          break;
          case 'れんあい':
            $data[':str']  = '['.$_COOKIE['jquery_chat_name'.$post['room']].'さん] ';
            $data[':str'] .= $omikuzi->Renai();
          break;
        }
        
      }
      
    //発言が空じゃなければ
      if($post['str']){
        log_write($data,$post['room'],false);
        log_write(MeCabManager::process($data, $post['type']),$post['room'],true);
      }
    break; 
    case 'readLog':
      readLog($post['room'],$post['append'],$post['lasthash'],$post['len']);
    break; 
  
/*
 * ログアウト
 */
    case 'logout':
      $log_str = $post['name'].$post['mes'];
      $data = array(
           ':room_id'      => $post['room']
          ,':chat_unique'  => 'LOGOUT'
          ,':hash'         => $hash
          ,':time'         => time()
          ,':chat_name'    => ADMIN_NAME
          ,':str'          => $log_str
          ,':remoote_addr' => $_SERVER["REMOTE_ADDR"]
          ,':name_color'   => ''
          ,':log_color'    => ''
          ,':chat_type'    => ''
          ,':created'      => date('Y-m-d')
      );
      
      
        log_write($data,$post['room']);
    break; 
/*
 * スタンプ
 */
    case 'gostump':
    //発言制限の時間
      if(!$_SESSION['write_limit']) $_SESSION['write_limit'] = time();
      if(time() < $_SESSION['write_limit']){
        write_stop();
        return ;
      }
      $_SESSION['write_limit'] = mktime(date('H'),date('i'),date('s')+write_limit,date('m'),date('d'),date('Y'));
      
      $data = array(
           ':room_id'      => $post['room']
          ,':chat_unique'  => $_COOKIE['jquery_chat_unique'.$post['room']]
          ,':hash'         => $hash
          ,':time'         => time()
          ,':chat_name'    => $_COOKIE['jquery_chat_name'.$post['room']]
          ,':str'          => $post['stump']
          ,':remoote_addr' => $_SERVER["REMOTE_ADDR"]
          ,':name_color'   => $name_color
          ,':log_color'    => $log_color
          ,':chat_type'    => 'STUMP'
          ,':created'      => date('Y-m-d')
      );
      
        log_write($data,$post['room']);
    break; 
/*
 * Googlemap
 */
    case 'gmap':
      $data = array(
           ':room_id'      => $post['room']
          ,':chat_unique'  => $_COOKIE['jquery_chat_unique'.$post['room']]
          ,':hash'         => $hash
          ,':time'         => time()
          ,':chat_name'    => $_COOKIE['jquery_chat_name'.$post['room']]
          ,':str'          => $post['val']
          ,':remoote_addr' => $_SERVER["REMOTE_ADDR"]
          ,':name_color'   => $name_color
          ,':log_color'    => $log_color
          ,':chat_type'    => 'GMAP'
          ,':created'      => date('Y-m-d')
      );

        log_write($data,$post['room']);
    break; 
/*
 * Image file
 */
    case 'file':
      if($post['file']):
        
        $data = array(
             ':room_id'      => $post['room']
            ,':chat_unique'  => $_COOKIE['jquery_chat_unique'.$post['room']]
            ,':hash'         => $hash
            ,':time'         => time()
            ,':chat_name'    => $_COOKIE['jquery_chat_name'.$post['room']]
            ,':str'          => $post['file']
            ,':remoote_addr' => $_SERVER["REMOTE_ADDR"]
            ,':name_color'   => $name_color
            ,':log_color'    => $log_color
            ,':chat_type'    => 'IMG'
            ,':created'      => date('Y-m-d')
        );

        log_write($data,$post['room']);
      endif;
    break; 
    
    case 'newLog':
      newLog();
    break; 
/*
 * stumpのサムネイルを得る
 */
    case 'stump':
      header("Content-type: application/xml");
      echo '<?xml version="1.0" encoding="UTF-8" ?> ' . "\n";
      echo '  <xml>'."\n"; 
      $res_dir = opendir('../../stump/thumbnail/');
        while( $file_name = readdir( $res_dir ) ){
          if($file_name != '.' AND $file_name != '..'){
           echo '  <item>'."\n"; 
           echo '      <stp>'.$file_name.'</stp>'."\n"; 
           echo '  </item>'."\n"; 
          }
        }
        closedir( $res_dir );
        echo '  </xml>'."\n"; 
    break; 
/*
 * リロード
 */
    case 'reload':
      $db = dbClass::connect();
      $reload[':room_id'] = $post['room'];
      $sql = "SELECT 
               MAX(id) AS id  
              ,hash
              ,chat_unique
              FROM chat_log WHERE room_id=:room_id ";
      try { 
        $stmt =  $db->prepare($sql);
        $stmt -> execute($reload);
        $row  =  $stmt->fetch(PDO::FETCH_ASSOC);
      } catch (PDOException $err) {
        //return $err->getMessage();
      }
      
      $flag = false;
      if($_SESSION['new_v_sqlite'] != $row['hash']){
        $flag = true;
      }
      

 header("Content-type: application/xml");
 echo '<?xml version="1.0" encoding="UTF-8" ?> ' . "\n";
 echo '  <xml>'."\n"; 
 echo '      <flag>'.$flag.'</flag>'."\n"; 
 echo '  </xml>'."\n"; 

    break; 
  }

  
/******************************************
 * 定義関数
 ******************************************/
function write_stop(){
  $message = '次の発言は'.write_limit.'秒経過するまで出来ません';
  header("Content-type: application/xml");
  echo '<?xml version="1.0" encoding="UTF-8" ?> ' . "\n";
  echo '<xml>'."\n"; 
  echo '      <limit>'.$message.'</limit>'."\n"; 
  echo '</xml>'."\n";
  exit;
}
  
  
/*
 * 全ログの取得
 */
function all_Log($roomid,$len=null){

  $db = dbClass::connect();
  $data[':room_id'] = $roomid;

  if($len == null){
    $data[':limit']   = LEN;
  }else{
    $data[':limit']   = $len;
  }
  
  $sql = "SELECT * FROM chat_log WHERE room_id=:room_id ORDER BY id DESC LIMIT 0,:limit";
  try { 
    $stmt =  $db->prepare($sql);
    $stmt -> execute($data);
    return  $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $err) {
    //return $err->getMessage();
  }
}

/*
 * 最新ログだけを取得
 */
function new_Log($roomid,$lasthash){

  $db = dbClass::connect();
  
 // $sql ="SELECT * FROM  chat_log WHERE  room_id = '001' AND  id > '18' ORDER BY id DESC";
  $data[':hash']    = $lasthash;
  $data[':room_id'] = $roomid;
  $sql ="SELECT * FROM  chat_log
          WHERE 
            room_id = :room_id
            AND  id > (SELECT id FROM chat_log WHERE hash = :hash )
             ORDER BY id DESC ";
  try { 
    $stmt =  $db->prepare($sql);
    $stmt -> execute($data);
    return  $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $err) {
    //return $err->getMessage();
  }
  
  
}


/*
 * ログをXMLに
 */
function readLog($roomid,$append,$lasthash,$len){
  
  $row = array();
  
// 最新ログだけを得る
  if($append){
    if(!$lasthash) return;
    $row = new_Log($roomid,$lasthash);
    $row = array_reverse(array($row[0],$row[1]));
  }
// 全てのログを得る
  else{
    $row = all_Log($roomid,$len);
    $row = array_reverse($row);
  }
  
  
  
  /*
   * $_SESSION['last_hash']
   * 自分が見た一番新しいログ以降のデータを得る
   */
  
 header("Content-type: application/xml");
 echo '<?xml version="1.0" encoding="UTF-8" ?> ' . "\n";
 echo '<xml>'."\n"; 
 

   foreach($row AS $log){
      if(date('Ymd',$log['time']) === date('Ymd')){
        $date = date('H:i',$log['time']);
      }else{
        $date = date('Y/m/d H:i',$log['time']);
      }
         echo '  <item xml:space="preserve">'."\n"; 
         echo '      <hash>'.$log['hash'].'</hash>'."\n"; 
         echo '      <cls>'.$log['log_sort'].'</cls>'."\n"; 
         echo '      <name>'.$log['chat_name'].'</name>'."\n"; 
         echo '      <log>'.$log['str'].'</log>'."\n"; 
         echo '      <date>'.$date.'</date>'."\n"; 
         echo '      <col1>'.$log['name_color'].'</col1>'."\n"; 
         echo '      <col2>'.$log['log_color'].'</col2>'."\n"; 
         echo '      <img>'.$log['chat_type'].'</img>'."\n"; 
         echo '  </item>'."\n"; 
   }

 
 echo '</xml>'."\n"; 
 
 $db = null;
 
 $last_key = count($row)-1;
 $_SESSION['new_v_sqlite'] = $row[$last_key]['hash'];
 
}


/*
 * ログを書き込む
 */
function log_write($data,$roomid,$ismecab){
  $db = dbClass::connect();
  $all_Log = all_Log($roomid);
  
  //$_SESSION['my_hash'] = $data[':hash']; //自分のチャット
  

  // 管理人発言の場合は li3
    if($data[':chat_unique'] === 'ADMIN' OR $data[':chat_unique'] === 'LOGOUT'){
      $data[':log_sort'] = 'li3';
    }else{
      
     $checkLog = array();
      foreach($all_Log AS $row){
        if($row['chat_unique'] != 'ADMIN') {
          $checkLog[] = $row;
        }
      }

      //管理人発言以外が無ければ li1
        // if(!$checkLog){
        //   $data[':log_sort'] = 'li1';
        // }else{
        //   if($data[':chat_unique'] === $checkLog[0]['chat_unique']){ //前の発言と自分の発言が一緒ならば
        //     $data[':log_sort'] = $checkLog[0]['log_sort'];
        //   }else{
        //     if($checkLog[0]['log_sort'] === 'li1'){
        //       $data[':log_sort'] = 'li2';
        //     }else{
        //       $data[':log_sort'] = 'li1';
        //     }
        //   }
        // }
        if ($ismecab) {
          $data[':log_sort'] = 'li1';
        }else{
          $data[':log_sort'] = 'li2';
        }
    }
    
    if($data['kuzi']){
      $data[':log_sort']    = 'li4';
      $data[':chat_unique'] = 'ADMIN';
      unset($data['kuzi']);
    }
    
    
    $sql = "INSERT INTO 'chat_log' ( 'id' , 'room_id' , 'chat_unique' , 'hash' , 'time' , 'chat_name' , 'str' , 'remoote_addr' , 'name_color' , 'log_color' , 'chat_type' ,'log_sort' , 'created' )
    VALUES ( NULL , :room_id , :chat_unique , :hash , :time , :chat_name , :str , :remoote_addr , :name_color , :log_color , :chat_type , :log_sort , :created ) ";
    try { 
      $stmt = $db->prepare($sql);
      $stmt->execute($data);
    } catch (PDOException $err) {
      //return $err->getMessage();
    }

}

/******************************************
 * データベース接続
 ******************************************/
class dbClass{
    static function connect(){
        try {
            $conn = new PDO("sqlite:".SQLITE);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
          return 'error';
            //return $err->getMessage();
        }
        return $conn; 
    }
}

/******************************************
 * MeCabによる処理
 ******************************************/

class Words{
  static $jojo = array(
    "ズキュウウウン",
"さすがディオ！おれたちにできない事を平然とやってのけるッ そこにシビれる！ あこがれるゥ！",
"君がッ 泣くまで 殴るのをやめないッ！",
"おれは人間をやめるぞ！ ジョジョ―――ッ！！",
"貧弱！ 貧弱ゥ！",
"ねーちゃん！ あしたっていまさッ！",
"誰だ？って聞きたそうな表情してんで自己紹介させてもらうがよ  おれぁ おせっかい焼きのスピードワゴン！ ロンドンの貧困街からジョースターさんが心配なんでくっついてきたぜ",
"こいつはくせぇッー！ ゲロ以下のにおいがプンプンするぜッ―――ッ！！こんな悪には出会ったことがねぇほどなァ―――ッ 環境で悪人になっただと？ ちがうねッ！！",
"メメタア",
"きさま―――いったい何人の生命をその傷のために吸い取った！？おまえは今まで食ったパンの枚数をおぼえているのか？",
"勇気とは怖さを知ることッ！ 恐怖を我が物とすることじゃあッ！ ギャイイン",
"ふるえるぞハート！ 燃えつきるほどヒ――――――ト！！",
"パパウ パウパウ 波紋カッタ―――ッ！！ フヒィーン",
"フフフ…この痛みこそ生のあかし この痛みあればこそ喜びも感じることができる これが人間か………………",
"ストレイツォ容赦せん！！",
"波紋？呼吸法だと？ フーフー吹くなら……このおれのためにファンファーレでも吹いてるのが似合っているぞッ！",
"猿（モンキー）が人間に追いつけるかーッ おまえはこのディオにとっての モンキーなんだよ ジョジョォォォォ－－－－－ッ！！",
"スピードワゴンはクールに去るぜ",
"逃げるんだよォ！スモーキー――――ッ！！ どけーヤジ馬どもーッ！！わあ～ッ！！なんだこの男ーッ",
"我がナチスの科学力はァァァァァァァアアア 世界一ィィィイイイイ",
"ハッピー うれピー よろピくね～",
"だから オレだってなんかしなくっちゃあな…カッコ悪くてあの世にいけねーぜ…………",
"おれが最後にみせるのは代々受け継いだ未来にたくす ツェペリ魂だ！ 人間の魂だ！",
"ＪＯＪＯ シーザーを悲しむのもさがすこともゆるしません…",
"へっへっへっへっへっ ま…またまたやらせていただきましたァン！",
"ブァカ者がァアアアア ナチスの科学は世界一チイイイイ!! サンタナのパワーを基準にイイイイイイ…このシュトロハイムの腕の力は作られておるのだアアアア!!",
"あァァァんまりだァァアァ",
"ウィン ウィン ウィンスル ウィン ウィンスル フフフスルスル ウィン ウィン ウィンスルスル この野郎～～～～",
"残るのはこのカーズ独りか…だが頂点に立つ者は常にひとり！",
"ドォーンカーズは2度と地球へは戻れなかった…鉱物と生物の中間の生命体となり永遠と宇宙空間をさまようのだ。そして、死にたいと思っても死ねないので―そのうち、カーズは考えるのをやめた。",
"ジョセフ・ジョースター！　きさま！　見ているなッ！",
"てめーの敗因は…たったひとつだぜ…DIO…たったひとつの単純な答えだ…てめーは俺を怒らせた",
"Exactly（そのとおりでございます）",
"このドグサレがァァ――――――ッ！！",
"世界（ザ・ワールド）ッ！ 時よ止まれ！",
"おれはやつの前で階段を登っていたと思ったらいつのまにか降りていたな…何を言っているのかわからねーと思うがおれも何をされたのかわからなかった…",
"人間は誰でも不安や恐怖を克服して安心を得るために生きる",
"ブラボー！ おお…ブラボー！！",
"おまえにかしてるツケさ 必ず払ってもらうぜ……………忘れっぽいんでな メモってたんだ",
"ロードローラーだッ！",
"なにがオラだッ！ 消化するときその口の中にてめーのクソをつめこんでやるぜッ！ ドブウ！",
"そうかな ズアッ",
"グッパオン うおっ",
"メ…ッセージ……で…す…これが…せい…いっぱい…です ジョースター…さん 受け取って…ください…伝わって………ください……",
"メギャン",
"やれやれ…犬好きの子供は見殺しには……できねーぜ!",
"じじいは……決して逆上するなと言った…しかし…それは…無理ってもんだッ！",
"あと味のよくないものを残すとか人生に悔いを残さないだとか…便所のネズミのクソにも匹敵する そのくだらない物の考え方が命とりよ！ クックック",
"このDIOにはそれはない・・・あるのはシンプルな　たったひとつの思想だけ・・・たったひとつ！勝利して支配する！それだけよ・・・それだけが満足感よ！",
"オラオラオラオラオラオラオラオラオラオラオラオラ無駄無駄無駄無駄無駄無駄無駄無駄無駄無駄無駄無駄",
"てめーは この空条承太郎がじきじきにブチのめす",
"おい…先輩 あんた…今 おれの この頭のことなんつった！",
"激しい喜びはいらない… そのかわり深い絶望もない……… 植物の心のような人生を… そんな平穏な生活こそ わたしの目標だったのに…………",
"だが断る",
"スゲーッ 爽やかな気分だぜ 新しいパンツをはいたばかりの正月元旦の朝のよーによォ~~~~~~~~~~~~ッ",
"この岸辺露伴が金やちやほやされるためにマンガを描いてると思っていたのかァ―――――ッ！！",
"２人ともブン殴るつもりだったんだよ おれ頭ワリイからよ~~~",
"質問を質問で返すなぁーっ！！  わたしが名前はと聞いているんだッ！ 疑問文は疑問文で答えろと学校で教えているのか？",
"自分を乗り越える事さ！ ぼくは自分の運をこれから乗り越える！！",
"おれは……反省すると強いぜ…",
"どうして30分だけなのよォオオオ~~~~~~~~~~ッ！！",
"そこで何をしている～～～～～ッ 見タナァ～～～～～っ オマエッ！ のぞき見に入ってきたというわけですかァーッ ただじゃあおきませンッ！ 覚悟してもらいマスッ！",
"パパとママを…………守るど!…………オラがッ！…………パパとママをあいつから守るどッ！",
"ウンまあ～いっ",
"あなたたち生きてる人間が町の誇りと平和を取り戻さなければ いったい誰がとり戻すっていうのよッ！",
"リアリティだよ! リアリティこそが作品に生命を吹き込むエネルギーでありリアリティこそがエンターテイメントなのさ",
"どうして ここから無事で帰れるのなら下痢腹かかえて公衆トイレ探してる方がズッと幸せって願わなくっちゃあならないんだ…？ ちがうんじゃあないか？",
"子供のころ…レオナルド・ダ・ビンチのモナリザってありますよね…あのモナリザがヒザのところで組んでいる手…あれ…初めて見た時…なんていうか…その…下品なんですが…勃起…しちゃいましてね…",
"この ジョルノ・ジョバァーナには 正しいと信じる夢がある",
"ギャング・スターにあこがれるようになったのだ！",
"ルカさん……2度 同じ事を言わせないでくださいよ………1度でいい事を2度 言わなけりゃあいけないってのは……そいつが頭が悪いって事だからです",
"覚悟とは！！ 暗闇の荒野に！！ 進むべき道を切り開く事だッ！",
"あなた…覚悟して来てる…………ですよね",
"ベロンッ　この味は！………ウソをついてる味だぜ……",
"きさまにはオレの心は永遠にわかるまいッ！",
"ジョルノッ！ おまえの命がけの行動ッ！ ぼくは敬意を表するッ！",
"ド低脳がァ―――ッ",
"ボラーレ・ヴィーア（飛んで行きな）",
"ブッ殺すと心の中で思ったならッ！ その時スデに行動は終わっているんだッ！",
"グレイト……フル・デッド………",
"とうおるるるるるるるるるるるるるるるるるるん、とぉるるる…ぶつッ！！ もしもし、はいドッピオです。",
"わかったよ プロシュート兄ィ！！ 兄貴の覚悟が！ 言葉でなく心で理解できた！",
"しょうがねーなぁ~~~~質問してんのはオレなのによォ……質問を質問で返すなよ……礼儀に反するってもんだぜ",
"ディ・モールト ディ・モールト （非常に 非常に） 良いぞッ！ 良く学習してるぞッ！",
"3個か！？ 甘いの3個ほしいのか？うおおう うおっ3個…イヤしんぼめ！！",
"トントントントントントントン",
"根掘り 葉掘り……ってよォ~~~~~~根を掘るってのは わかる……スゲーよくわかる 根っこは土の中に埋っとるからな…だが葉掘りって部分はどういう事だああ~~~~~っ！？",
"任務は遂行する部下も守る両方やらなくっちゃあならないってのが幹部のつらいところだな 覚悟はいいか？ オレはできてる",
"ゆるさねぇッ！ あんたは今再びッ！ オレの心を裏切ったッ！",
"アリーヴェ デルチ！（さよならだ）",
"アリーヴェ デルチ（さよならよ）",
"オレは帝王だ  おれが目指すものは絶頂であり続けることだ  ここで逃げたら…その誇りが失われる 次はないッ…！",
"人の成長は…………未熟な過去に打ち勝つことだとな…",
"帝王はこのディアボロだッ！！ 依然変わりなくッ！",
"そうだな…わたしは結果だけを求めてはいない 結果だけを求めていると人は近道をしたがるものだ…近道した時 真実を見失うかもしれない やる気も次第に失せていく",
"人が人を選ぶにあたって最も大切なのは信頼なんだ それに比べたら頭がいいとか才能があるなんて事はこのクラッカーの歯クソほどの事もないんだ・・・",
"どの留置係にィィ―――っ！？ オーマイガッ！今日の明け方に………目が覚めちゃって………月明かりのさす あそこの窓の鉄格子を形を見てたら なんか その",
"人はみんなあしたは月曜日ってのは嫌なものなんだ でも…必ず楽しい土曜日がやってくるって思って生きている いつも月曜ってわけじゃあないのよ！",
"祝福しろ",
"用意をするんだ てめーがこの世に生まれて来たことを後悔する用意をだ！",
"復讐とは自分への運命への決着をつけるためにあるッ！",
"ブタの逆はシャケだぜ ブタはゴロゴロした生活だがシャケは流れに逆らって川をのぼるッ！気に入った―――ッ！！",
"一言でいい…許すと…ここを生き延びたなら結婚の許可を与えると！",
"うおっ 人類の夜明けだわ こりゃ",
"やるっていうのなら受けてたつわ…アメリカ方式 フランス方式 日本方式",
"イタリア ナポリ方式　世界のフィンガーくたばりやがれよ",
"君は引力を信じるか？",
"おまえはわたしにとって、釈迦の手のひらを飛び回る孫悟空ですらない",
"人が敗北する原因は…恥のためだ。人は恥のために死ぬ。",
"落ちつくんだ素数を数えて落ちつくんだ…素数は１と自分の数でしか割ることのできない孤独な数字…わたしに勇気を与えてくれる",
"明日死ぬとわかっていても、覚悟があるから幸福なんだ！ 覚悟は絶望を吹き飛ばすからだッ！",
"ぼくの名前はエンポリオです",
"メメタアアア",
"ピザ・モッツァレラ♪  ピザ・モッツァレラ♪",
"そこちょっと失礼（し・トウ・れい）ィィィィィ～～～",
"圧迫よォッ！ 呼吸が止まるくらいッ！ 興奮して来たわッ！ 早く！ 圧迫祭りよッ！ お顔を圧迫してッ！",
"飢えなきゃ勝てない ただし あんなＤioなんかより ずっとずっともっと気高く飢えなくては！",
"切り裂いた首のその傷はッ！ オレがいた人間世界の悲惨の線だ・・・",
"THE WORLD（ザ ワールド） オレだけの時間だぜ",
"オレはこのＳＢＲレースでいつも最短の近道を試みたが 一番の近道は遠回りだった 遠回りこそが俺の最短の道だった",
"おっと 会話が成り立たないアホがひとり登場~~~~ 質問分に対し質問分で答えるとテスト０点なの知ってたか？マヌケ",
"オレのこの能力のスタンド名だ チョコイレト・ディスコ ただのそれしか言わない 以上で終わりだ",
"ようこそ………男の世界へ…………",
"失敗というのは…………いいかよく聞けッ！ 真の失敗とはッ！ 開拓の心を忘れ！ 困難に挑戦する事に無縁のところにいる者たちの事をいうのだッ！",
"ズギュウウウン",
"…………出来ません……わたしの名前はルーシー・スティール……わたしが愛しているのはただひとり…夫だけです スティールという姓あってこそのルーシー",
"興奮して来た………服を脱げ",
"我が心と行動に一点の曇りなし…………！ 全てが正義だ",
"でも 断る",
"どジャアアあああ～～～～～～ン",
"冬のナマズみたいにおとなしくさせるんだッ！！",
"何なんだああああ オレの左眼ばかり………その女を冬のナマズみたいにおとなしくさせろオオ――――――ッ",
  );
} 

 
class MeCabManager{
  
  static $options = array('-d', '/usr/local/lib/mecab/dic/mecab-ipadic-neologd');
  
  static function process($data, $type){
    
    $data[':chat_name'] = 'MeCab';
    error_log($type);
    switch ($type){
      case 'jojo';
          $keys = array_rand(Words::$jojo, 2);
          $data[':str'] = self::markov_chain(Words::$jojo[$keys[0]].$data[':str'].Words::$jojo[$keys[1]]);
      break;
      case 'markov';
          $data[':str'] = self::markov_chain($data[':str']);
      break;
      default;
          $data[':str'] = self::decompose($data[':str']);
      break;
    }
    return $data;
  }
  
  static function decompose($str){
    $mecab = new MeCab_Tagger(self::$options);
    $nodes = $mecab->parseToNode($str);
    $ret = '';
    foreach ($nodes as $n)
    {
      if ($n->getSurface() == '') continue;
      $ret .= $n->getSurface() . "\n";
      $ret .= $n->getFeature() . "\n\n";
    }
    return $ret;
  }
  
  static function markov_chain($str){
    $str = mb_convert_kana($str, "R");
    $str = htmlspecialchars($str);
    $patterns = array("/[\n\r]/", "/　+/");
    $replacements = array("", "");
    $str = preg_replace($patterns, $replacements, $str);
    $mecab = new MeCab_Tagger(self::$options);
    $nodes = $mecab->parseToNode($str);
    $words = array();
    foreach ($nodes as $n){
      if ($n->getSurface() == '') continue;
      $words[] = $n->getSurface();
    }
    return self::make_chain($words);
  }
  
  static function make_chain($words){
    $max_cnt = 100;
    $EOS = "EOS";
    $break_mark = "。";
    $break_frequency = 2;
    $cnt = count($words);
    $ary = array();
    
    for ($i = 0; $i < $cnt; $i++) {
      if ($i == 0) {
        $prefix1 = $words[$i];
        $prefix2 = $words[($i + 1)];
        $suffix = $words[($i + 2)];
        $ary[$prefix1] = array($prefix2 => array($suffix));
      } 
      elseif ($i < ($cnt - 1)) {
        $prefix1 = $words[($i - 1)];
        $prefix2 = $words[$i];
        $suffix = $words[($i + 1)];
        if (!(isset($ary[$prefix1]))) {
          $ary[$prefix1] = array($prefix2 => array($suffix));
        } 
        elseif (isset($ary[$prefix1]) && !(isset($ary[$prefix1][$prefix2]))) {
          $ary[$prefix1][$prefix2] = array($suffix);
        } 
        else {
          if (!in_array($suffix, $ary[$prefix1][$prefix2])) {
            $ary[$prefix1][$prefix2] = array_merge($ary[$prefix1][$prefix2], array($suffix));
          }
        }
      } 
      elseif ($i == ($cnt - 1)) {
        $prefix1 = $words[($i - 1)];
        $prefix2 = $words[$i];
        $suffix = $words[$i];
        if (!(isset($ary[$prefix1]))) {
          $ary[$prefix1] = array($prefix2 => array($suffix));
        } 
        else {
          $ary[$prefix1][$prefix2] = array($suffix);
        }
      }
    }
    // error_log(print_r($ary));
    
    $first = key($ary);
    $second = array_rand($ary[$first]);
    srand();
    $num = rand(0, (count($ary[$first][$second]) - 1));
    $third = $ary[$first][$second][$num];
    $data .= $first . $second;
    
    for ($i = 0; $i < $max_cnt; $i++) {
      $first = $third;
      $second = array_rand($ary[$first]);
      srand();
      $num = rand(0, (count($ary[$first][$second]) - 1));
      $third = $ary[$first][$second][$num];
      
      if ( ($second == $EOS) || ($third == $EOS) ) {
        break;
      }
      
      srand();
      $num = rand(1, $break_frequency);
      if ((($break_frequency - $num) == 0) && ($first == $break_mark)) {
        $data .= $first . "\n" . $second;
      } 
      else {
        $data .= $first . $second;
      }
    }
    $data = mb_convert_kana($data, "a");
    return $data;
  }
  
  static function searchAvailableValues ($table, $key){
    $values = array();
    foreach ( $table as $row ) {
        if ( $row[0] == $key[0] && $row[1] == $key[1] ) {
            $values[] = $row[2];
        }
    }
    return $values;
  }
}
