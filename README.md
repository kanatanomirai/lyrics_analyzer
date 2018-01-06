# lyrics_analyzer

歌詞を形態素解析し、その結果をデータベースに保存するプログラムです。  
  
# 動作確認済み環境  

- Mac OS 10.12.6

# 使い方  

1. 歌詞データと曲タイトルデータを用意します。  
歌詞データと曲タイトルデータはテキストファイルであり、以下のような形式となっていることが条件です。  
  
・ 歌詞データ  
1曲目の歌詞  
1曲目の歌詞  
1曲目の歌詞  
1曲目の歌詞  
  
2曲目の歌詞  
2曲目の歌詞  
2曲目の歌詞  
  
3曲目の歌詞  
・・・  

・ 曲タイトルデータ  
1曲目の曲タイトル  
2曲目の曲タイトル  
3曲目の曲タイトル  
・・・  
  
2. JUMAN++をインストールします。  
[日本語形態素解析システム JUMAN++](http://nlp.ist.i.kyoto-u.ac.jp/index.php?JUMAN++)

3. データベースを用意します。  
このプログラムではMySQLを使用しています。

4. プログラムを実行します。  
歌詞を解析したい場合は、lyrics_analyze.phpを、曲タイトルを解析したい場合は、musictitle_analyze.phpを実行してください。  
  
  
# English
This is a program that morphologically analyzes lyrics and stores the results in a database.  
  
# System Requirements 

- Mac OS 10.12.6

# how to use 

1. Prepare lyrics data and song title data.   
The lyrics data and song title data are text files and it must be in the following format.   
  
・ lyrics data  
first song lyrics  
first song lyrics   
first song lyrics    
first song lyrics    
  
second song lyrics  
second song lyrics  
second song lyrics  
  
third song lyrics   
・・・  

・ song title data  
first song title  
second song title  
third song title  
・・・  
  
2. Install JUMAN++.  
[日本語形態素解析システム JUMAN++](http://nlp.ist.i.kyoto-u.ac.jp/index.php?JUMAN++)

3. Prepare the database.  
This program uses MySQL.  

4. Run the program.  
If you want to analyze lyrics, please run lyrics_analyze.php, if you want to analyze the song title, please run musictitle_analyze.php.  
