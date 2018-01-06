<?php
//参考： https://qiita.com/SOJO/items/a42fe56334ce810b97be

require_once 'phpmysql.php';

// 結果用配列
$all_result = array();
$music_result = array();

// データベース接続用インスタンス
$db = new MyDB();
$offset = 0;

// 重複エラーコード
const DUPLICATE_CODE = 1062;

function debugEcho($str){
  echo $str . "\n";
}

function juman($lyrics) {

  debugEcho("juman start.");

  global $all_result, $db;

  // 品詞（名詞=6、動詞=2、形容詞=3、副詞=8)
  $partOfSpeech = array(6, 2, 3, 8);

  // 歌詞中のアルファベット削除
  $lyrics = preg_replace('/[a-zA-Z]/', ' ', $lyrics);

  // jumanのコマンドを実行
  // EOSを除去し，改行で区切
  $output = shell_exec(sprintf('echo  %s | jumanpp', escapeshellarg($lyrics)));
  $output = array_reverse(preg_split("/EOS|\n/u", $output));

  // 単語に別の意味が含まれる場合、
  // 同じ単語でも @(atmark) が付いて行が別れてしまうので、
  // それを合わせる処理
  // 配列を逆順にしてから処理することで、複数行に及ぶものにも対応
  foreach ($output as $out_key => $out_value) {
    if (isset($out_value[0]) && $out_value[0] == "@") {
      $output[$out_key+1] .= " " . $output[$out_key];
      $output[$out_key] = "";
    }

    // 必要な要素のみを切り出し
    $split_text = explode(" ", $output[$out_key]);
    if(count($split_text) >= 5){
      // kakasiによる漢字、カタカナ→ひらがな変換（表記ゆれ対策）
      $hiragana_text = shell_exec(sprintf('echo  %s | kakasi -JH -KH -s -i utf-8 -o utf-8', escapeshellarg($split_text[2])));
      // 変換後の文字列中の空白削除
      $hiragana_text = str_replace(array(" ", "　"), "", $hiragana_text);
      $output[$out_key] = $split_text[1] . " " . $split_text[2] . " " . $split_text[3] . " " . $split_text[4];

      foreach($partOfSpeech as $value){
        if($split_text[4] == $value){
          // 解析結果を追加
          $all_result[] = str_replace(PHP_EOL, '', $hiragana_text . " " . $split_text[2] . " " . $split_text[3]);
          // 曲ごとの解析結果を追加
          $music_result[] = str_replace(PHP_EOL, '', $hiragana_text . " " . $split_text[2] . " " . $split_text[3]);
        }
      }
    }
  }
  // 空の要素を取り除く
  $return_value = array_filter(array_reverse($output), 'strlen');
  $return_value = array_values($return_value);

  // 解析結果（表層形 読み 見出し語 品詞大分類 品詞大分類ID 品詞細分類 品詞細分類ID 活用型 活用型ID 活用形 活用形ID 意味情報）
  //var_dump($return_value);

  // 曲ごとの解析結果を表示
  getResult($music_result);
  debugEcho("juman end.");
}

// 曲タイトルデータを読み込む
function readMusicTitle($filename){

  global $db, $title;

  // fopenでファイルを開く（読み込みモード）
  $fp = fopen($filename, 'r');
  debugEcho("read start.");

  // ループ処理
  while(!feof($fp)){
    // ファイルを1行読み込み
    $title = fgets($fp);
    $title = str_replace(array("\r", "\n"), '', $title);

    // 空行はスキップ
    if(strcmp("\n", $title) == 0){
      continue;
    }

    // DBにSQLを送信
    $db_result = $db->query("INSERT INTO music_title(title) VALUES('$title')");

    //データベースに登録済みであれば重複フラグを立てる
    if(checkDuplicate($db_result["error"])){
      debugEcho("duplicate title:" . $title);
      $is_duplicate = true;
    }else{
      debugEcho("read title:" . $title);
      $is_duplicate = false;
    }
    
    // 歌詞データ読み込み
    readLyrics('lyrics.txt', $is_duplicate);
  }
  // ファイルを閉じる
  fclose($fp);
  debugEcho("read complete!");
}

function readLyrics($filename, $flag){

  global $offset;
  $lyrics = '';

  // fopenでファイルを開く（読み込みモード）
  $fp = fopen($filename, 'r');

  fseek($fp, $offset);

  // ループ処理
  while(!feof($fp)){
    // ファイルを1行読み込み
    $tmp_lyrics = fgets($fp);
    
    // 改行は曲の終わりを示す
    if(strcmp("\n", $tmp_lyrics) == 0){
      break;
    }else{
      // 歌詞格納
      $lyrics .= $tmp_lyrics;
    }
  }

  // juman++による形態素解析は曲タイトルが重複していないときのみ
  if(!$flag){
    debugEcho("read lyrics.");
    juman($lyrics);
  }

  $lyrics = '';
  $offset = ftell($fp);

  // ファイルを閉じる
  fclose($fp);
}

// 解析結果を取得
function getResult($arr_result){
  global $db, $title;

  $result = array_count_values($arr_result);
  arsort($result);
  
  $insert_sql = "INSERT INTO lyrics(word_hiragana, word, part_of_speech, num, title) VALUES";

  // 最終結果
  foreach($result as $key => $val){
    $lyrics_count = $key . " " . $val;
  //  echo $lyrics_count . "\n";
    $split_lyrics = explode(" ", $lyrics_count);
    $insert_sql .= "('$split_lyrics[0]', '$split_lyrics[1]', '$split_lyrics[2]', '$split_lyrics[3]', '$title'), ";
  }
  // SQL文の最後をセミコロンにする
  $insert_sql = rtrim($insert_sql, ' ');
  $insert_sql = rtrim($insert_sql, ',');

  // echo $insert_sql;
  $db->query($insert_sql);
}

// データベース重複エラーチェック
function checkDuplicate($str){
  $error_str = explode(" ", $str);
  $error_num = rtrim($error_str[0], ':');

  if($error_num == DUPLICATE_CODE){
    return true;
  }else{
    return false;
  }
}

// 処理時間計測
$time_start = microtime(true);

readMusicTitle('musictitle.txt');

var_dump($db->query("SELECT word_hiragana, part_of_speech, SUM(num) AS total_num FROM lyrics GROUP BY word_hiragana, part_of_speech ORDER BY total_num DESC LIMIT 50"));

// 処理時間計測
$time = microtime(true) - $time_start;
echo "processing time：{$time} sec\n";