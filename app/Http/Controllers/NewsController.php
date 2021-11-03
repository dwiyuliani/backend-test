<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NewsController extends Controller
{

    public function __construct()
    {
        $this->module = 'news';
    }


    public function create(Request $request)
    {
        $this->validate($request, [
            "title" => "required",
            "description" => "required",
            "content" => "required",
            "status" => "required"
        ]);

        $data = $request->all();
        try {
            $news = News::create($data);
            $this->set_tag($news['id'], @$data['tag']);
            $this->set_topic($news['id'], @$data['topic']);
        } catch (\Exception $e) {
            return response()->json(response_error('Failed to insert data' . $e), 400);
        }
        return response()->json(response_success('Success Insert Data News!', $news), 200);
    }

    public function update($id, Request $request)
    {
        try {
            $news = News::findOrFail($id);
            $data = $request->all();
            try {
                $news->update($data);
                $this->set_tag($news['id'], @$data['tag']);
                $this->set_topic($news['id'], @$data['topic']);
            } catch (\Exception $e) {
                return response()->json(response_error('Failed Updated News'), 409);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json(response_error($e->getMessage()), 409);
        }
        return response()->json(response_success('Update Data Tag Berhasil !', $news), 200);
    }

    public function delete($id)
    {
        try {
            News::Where('id', $id)->update((['status' => 'deleted']));
        } catch (ModelNotFoundException $e) {
            return response()->json(response_error($e->getMessage()), 409);
        }
        return response()->json(response_success('Delete Data news Berhasil !', ''), 200);
    }


    public function get(Request $request)
    {
        $status = $request->input('status');
        $topic = $request->input('topic');
        $page = empty($request->input('page')) ?  'all' : (int)$request->input('page');
        $limit = !empty($request->input('limit')) ? (int)$request->input('limit') : 100;
        $offset = is_numeric($page) ? ($page - 1) * $limit : 0;


        $key = [
            'page' =>  $page,
            'limit' => $limit,
            'status' => $status,
            'topic' => $topic
        ];

        $key_redis =  $this->module . "-" . implode('-', $key);
        $data_cache = redis_cache($key_redis);
        $data = ['list' => []];

        if ($data_cache != false) 
            return response()->json(response_success('Berhasil mengambil data redis.', $data_cache), 200);
        

        try {
            if (empty($topic))
                $query = DB::table($this->module);

            if (!empty($topic))
                $query = News::whereHas('topic', function ($q) use ($topic) {
                    $q->where('topic_id', $topic);
                });


            $query = $query->orderBy('id', 'desc')
                ->limit($limit)
                ->offset($offset);

            if (!empty($status))
                $query = $query->where('status', $status);

            $news = $query->get();

            if ($news->isEmpty())
                return response()->json(response_error('Data News tidak ditemukan'), 404);


            $data_news=array();

            foreach ($news as $row) {
                $rows['id'] =$row->id;
                $rows['title'] = $row->title;
                $rows['description'] = $row->description;
                $rows['content'] = $row->content;
                $rows['status'] = $row->status;
                $rows['tag'] = $this->get_tag($row->id);
                $rows['topic'] = $this->get_topic($row->id);
                $data_news[]=$rows;
            }


            $data['list'] = $data_news;
            $redis = redis_cache($key_redis, $data);
            return response()->json(response_success('Berhasil mengambil data topic db.', $redis), 200);
        } catch (\Exception $e) {
            return response()->json(response_error('Failed to get data' . $e), 400);
        }
    }


    public function get_tag($id){
        $data = [];
        if(isset($id))
            $data = DB::table('tag')
                    ->join('news_tag','news_tag.tag_id','=','tag.id')
                    ->where('deleted_at',NULL)
                    ->where('news_id',$id)
                    ->select('id','name')
                    ->get();

        
        return $data;
    }

    public function get_topic($id){
        $data = [];
        if(isset($id))
            $data = DB::table('topic')
                ->join('news_topic','news_topic.topic_id','=','topic.id')
                ->where('deleted_at',NULL)
                ->where('news_id',$id)
                ->select('id','name','description')
                ->get();
        return $data;
    }



    public function set_tag($news_id, $data)
    {
        DB::table('news_tag')->where('news_id', $news_id)->delete();
        $now = Carbon::now('utc')->toDateTimeString();
        if ($data) {
            $data = explode(',',$data);
            $insert = array();
            foreach ($data as $row) {
                $insert[] = array(
                    'news_id' => $news_id,
                    'tag_id' => $row,
                    'created_at' => $now
                );
            }
            DB::table('news_tag')->insert($insert);
        }
    }

    public function set_topic($news_id, $data)
    {
        DB::table('news_topic')->where('news_id', $news_id)->delete();
        $now = Carbon::now('utc')->toDateTimeString();

        if ($data) {
            $data = explode(',',$data);
            $insert = array();
            foreach ($data as $row) {
                $insert[] = array(
                    'news_id' => $news_id,
                    'topic_id' => $row,
                    'created_at' => $now
                );
            }
            DB::table('news_topic')->insert($insert);
        }
    }
}
