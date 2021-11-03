<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;


class TagController extends Controller
{


    public function __construct()
    {
        $this->module = 'tag';
    }


    public function create(Request $request)
    {
        $this->validate($request, [
            "name" => "required|unique:tag|max:50",
        ]);


        $data = $request->all();
        try {
            $tag = Tag::create($data);
        } catch (\Exception $e) {
            return response()->json(response_error('Failed to insert data' . $e), 400);
        }
        return response()->json(response_success('Insert Data Tag Berhasil !', $tag), 200);
    }

    public function update($id, Request $request)
    {
        $this->validate($request, [
            "name" => "required|unique:tag|max:50",
        ]);

        try {
            $tag = Tag::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(response_error($e->getMessage()), 409);
        }

        $tag->update($request->all());
        return response()->json(response_success('Update Data Tag Berhasil !', $tag), 200);
    }

    public function delete($id)
    {
        try {
            $tag = Tag::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(response_error($e->getMessage()), 409);
        }
        $tag->delete();
        return response()->json(response_success('Delete Data Tag Berhasil !', $tag), 200);
    }

    public function get(Request $request)
    {

        $name = $request->get('name');

        $page = ($request->has('page')) ? intval($request->get('page'))  :0  ;
        $limit = ($request->has('limit')) ? (int)$request->get('limit') : 100;
        $offset = is_numeric($page) ? ($page - 1) * $limit : 0;

        $key = [
            'page' =>  $page,
            'limit' => $limit,
            'name' => $name
        ];

        $key_redis = $this->module . "-" . implode('-', $key);
        $tag_cache = redis_cache($key_redis);
        $data = ['list' => [], 'limit' => $limit];

        if ($tag_cache != false) {
            return response()->json(response_success('Berhasil mengambil data tag redis.', $tag_cache), 200);
        }

        try {
            $query = DB::table('tag')
                ->limit($limit)
                ->offset($offset)
                ->orderBy('tag.id', 'desc')
                ->where('deleted_at', null);

            if (!empty($name))
                $query = $query->whereRaw("LOWER(name) LIKE  '%" . strtolower($name) . "%'");

            $tag = $query->get();

            if ($tag->isEmpty())
                return response()->json(response_error('Data article tidak ditemukan'), 404);

            $data['list'] = $tag;
            $redis = redis_cache($key_redis, $data);
            return response()->json(response_success('Berhasil mengambil data tag db.', $redis), 200);
        } catch (\Exception $e) {
            return response()->json(response_error('Failed to get data' . $e), 400);
        }
    }
}
