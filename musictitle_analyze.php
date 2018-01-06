<?php
//参考： https://qiita.com/SOJO/items/a42fe56334ce810b97be

require_once 'phpmysql.php';

// 結果用配列
$all_result = array();
$music_result = array();

$offset = 0;

function debugEcho($str){
  echo $str . "\n";
}

function juman($lyrics) {

  debugEcho("juman start.");

  global $all_result;

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
          // 曲タイトルごとの解析結果を追加
          $music_result[] = str_replace(PHP_EOL, '', $hiragana_text . " " . $split_text[2] . " " . $split_text[3]);
        }
      }
    }
  }

  $return_value = array_filter(array_reverse($output), 'strlen'); // 空の要素を取り除く
  $return_value = array_values($return_value);

  // 解析結果（表層形 読み 見出し語 品詞大分類 品詞大分類ID 品詞細分類 品詞細分類ID 活用型 活用型ID 活用形 活用形ID 意味情報）
  //var_dump($return_value);

  // 曲ごとの解析結果を表示
  if(empty($music_result)){
    $music_result[] = " ";
  }

  getResult($music_result);
  debugEcho("juman end.");
}

// 曲タイトルデータを読み込む
function readMusicTitle($filename){

  global $title;

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

    // タイトル読み込み
    juman($title);
    $title = '';
  }
  // ファイルを閉じる
  fclose($fp);
  debugEcho("read complete!");
}

// 解析結果を取得
function getResult($arr_result){

  if(count($arr_result) == 0){
    return;
  } 

  $result = array_count_values($arr_result);
  arsort($result);
  
  // 最終結果
  foreach($result as $key => $val){
    $lyrics_count = $key . " " . $val;
    echo $lyrics_count . "\n";
  }
}

// 処理時間計測
$time_start = microtime(true);

readMusicTitle('musictitle.txt');

getResult($all_result);

// 処理時間計測
$time = microtime(true) - $time_start;
echo "processing time：{$time} sec\n";