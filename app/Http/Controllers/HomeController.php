<?php
// ルーティング先のメソッド、機能（web.phpからの指定先がこのファイル）を記述するファイル 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\MemoTag;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //viewでcreate.blade.phpの内容を表示する
        return view('create'); //compactで変数を渡せる
    }

    // formからデータを送信される用のルーティングメソッド
    // Request $request POSTの関数の引数に書くことでHTTPリクエストに関わる関数を使用できるようになる
    public function store(Request $request)
    {
        $posts = $request->all();

        // dump dieの略　→　メソッドのとった引数を展開して止める　→　データの確認
        //トランザクション処理を開始
        DB::transaction(function () use ($posts) {
            $memo_id = Memo::insertGetId(['content' => $posts['content'], 'user_id' => \Auth::id()]);
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists();
            //タグが入力されているかチェック
            // 新規タグが既にtagsテーブルに存在しているかチェック
            if (!empty($posts['new_tag'] && !$tag_exists)) {
                //tagsテーブルにインサートしつつ、インサートIDを取得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsにインサートして、メモとタグを紐付ける
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }
            //既存タグが紐付けられた場合ー＞memo_tagsにインサート  
            if (!empty($posts['tags'][0])) {
                foreach ($posts['tags'] as $tag) {
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
                }
            }
        });

        return redirect(route('home'));
    }

    public function edit($id) //編集のViewメソッド　ルーティングで指定したURLの｛id｝が使用できる　どれを編集するかをこのidで取得
    {
        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
            ->leftJoin('memo_tags', 'memo_tags.memo_id', '=', 'memos.id') //メモタグsのメモidがmemos.idと一致するのだけ合体する
            ->leftJoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
            ->where('memos.user_id', '=', \Auth::id()) //今のログインしているユーザを絞る memosにもtagsにもuser_idあるから、memos.user_idとしてあげる
            ->where('memos.id', '=', $id) //複数ある中のメモから、編集中のメモ一つだけを絞る
            ->whereNull('memos.deleted_at')
            ->get(); //find　URLのGETできたid,主キーをfindに渡すことで、メモを一つとってこれる

        $include_tags = []; //メモに紐付けされているタグIDだけを入れる配列、これ使用してViewにデフォルトでチェック入れる
        foreach ($edit_memo as $memo) {
            array_push($include_tags, $memo['tag_id']);
        }

        return view('edit', compact('edit_memo', 'include_tags')); //compactで変数を渡せる
    }

    public function update(Request $request) //編集のDB機能のメソッド
    {
        $posts = $request->all();
        // dd($posts);
        //トランザクションスタート
        DB::transaction(function () use($posts){
            //memo_id（editから教えてもらった、編集しているメモのid)のcontentをpostsのcontentへアップデート、書き換える
            Memo::where('id', $posts['memo_id'])->update(['content' => $posts['content']]);
            //いったんメモとタグの紐付けを削除
            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();
            //再度メモとタグの紐付け
            foreach ($posts['tags'] as $tag){
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag]);
            }
            //もし、新しいタグの入力があれば、インサートして紐付ける
            $tag_exists = Tag::where('user_id', '=', \Auth::id())->where('name', '=', $posts['new_tag'])->exists();
            //タグが入力されているかチェック
            // 新規タグが既にtagsテーブルに存在しているかチェック
            if (!empty($posts['new_tag'] && !$tag_exists)) {
                //tagsテーブルにインサートしつつ、インサートIDを取得
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                //memo_tagsにインサートして、メモとタグを紐付ける
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);
            }

        });
        //トランザクションはここまで
        return redirect(route('home'));
    }

    public function destory(Request $request) //編集のDB機能のメソッド
    {
        $posts = $request->all();
        //dd(); dump dieの略　→　メソッドのとった引数を展開して止める　→　データの確認

        //memo_id（editから教えてもらった、編集しているメモのid)のdeleted_atをdate（形式,現在時刻を取る）へアップデート、書き換える
        Memo::where('id', $posts['memo_id'])->update(['deleted_at' => date("Y-m-d H:i:s", time())]);
        return redirect(route('home'));
    }
}
