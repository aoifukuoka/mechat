# mechat
MeCabとチャットできるツールです。

##使い方
1. Mecab, NEologdをインストール(NEologdは/usr/local/lib/mecab/dic/mecab-ipadic-neologdへ配置されることを想定しているので注意)
2. phpmecabをインストールしてPHPからMecabを使用可能にする。
3. httpサーバーのDocumentRootを/chat/index.htmlへ設定する。
4. /chat/db/chat.sqliteのパーミッションを書き込み可能にする。
5. ブラウザで設定したアドレスにアクセスすれば、チャット開始。

##遊び方
URLに付与するtypeパラメータにより返答が変化する。

* パラメータなし 辞書NEologdを使用して品詞分解して返信。
* ?type=markov 送信された内容をマルコフ連鎖して返信。
* ?type=jojo 送信された内容の文頭と文末にそれぞれランダムにJOJOの名言を付与しマルコフ連鎖して返信。

##その他
jojo.pyはJOJOの名言をスクレイピングする際に使用したもの。
使用するにはpythonのmechanize, bs4が必要です。
現在はNaverまとめのURLにのみ対応。
