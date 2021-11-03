<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;


class TopicController extends Controller
{
    public function __construct()
    {
        $this->module = 'topic';
    }
    public function create(Request $request)
    {
        $this->validate($request, [
            "name" => "required|max:50",
            "description" => "required|max:100",
        ]);


        $data = $request->all();
        try {
            $topic = Topic::create($data);
        } catch (\Exception $e) {
            return response()->json(response_error('Failed to insert data' . $e), 400);
        }
        return response()->json(response_success('Success insert data !', $topic), 200);
    }

    public function update($id, Request $request)
    {

        try {
            $topic = Topic::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(response_error($e->getMessage()), 409);
        }

        $topic->update($request->all());
        return response()->json(response_success('Success Update Data !', $topic), 200);
    }

    public function delete($id)
    {
        try {
            $topic = Topic::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(response_error($e->getMessage()), 409);
        }
        $topic->delete();
        return response()->json(response_success('Success Delete Data !', $topic), 200);
    }

    public function get(Request $request)
    {

        $name = $request->get('name');

        $page = ($request->has('page')) ? intval($request->get('page'))  : 0;
        $limit = ($request->has('limit')) ? (int)$request->get('limit') : 100;
        $offset = is_numeric($page) ? ($page - 1) * $limit : 0;

        $key = [
            'page' =>  $page,
            'limit' => $limit,
            'name' => $name
        ];

        $key_redis = $this->module . "-" . implode('-', $key);
        $topic_cache = redis_cache($key_redis);
        $data = ['list' => []];
        if ($topic_cache != false) {
            return response()->json(response_success('Berhasil mengambil data topic redis.', $topic_cache), 200);
        }

        try {
            $query = DB::table($this->module)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->offset($offset)
                ->where('deleted_at', null);

            if (!empty($name))
                $query = $query->whereRaw("LOWER(name) LIKE  '%" . strtolower($name) . "%'");

            $topic = $query->get();

            if ($topic->isEmpty())
                return response()->json(response_error('Data topic tidak ditemukan'), 404);

            $data['list'] = $topic;
            $redis = redis_cache($key_redis, $data);
            return response()->json(response_success('Berhasil mengambil data topic db.', $redis), 200);
        } catch (\Exception $e) {
            return response()->json(response_error('Failed to get data' . $e), 400);
        }
    }
}
