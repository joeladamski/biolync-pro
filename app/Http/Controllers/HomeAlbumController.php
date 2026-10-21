<?php
namespace App\Http\Controllers;

use App\Support\HomeAlbum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeAlbumController extends Controller
{
    public function save(Request $request)
    {
        $data=$request->validate([
            'album_enabled'=>'required|boolean','album'=>'nullable|array|max:24',
            'album.*.id'=>'nullable|string|regex:/^[a-f0-9]{16}$/',
            'album.*.image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:8192|dimensions:max_width=6000,max_height=6000',
            'album.*.width'=>'required|integer|min:120|max:580','album.*.order'=>'nullable|integer|min:0|max:999',
            'album.*.alt'=>'nullable|string|max:200','album.*.caption'=>'nullable|string|max:200',
            'album.*.url'=>['nullable','url','max:2048','regex:/^https?:\/\//i'],
            'album.*.new_tab'=>'nullable|boolean','album.*.remove'=>'nullable|boolean',
            'album.*.icon'=>['nullable',Rule::in(array_keys(HomeAlbum::icons()))],
        ]);
        $rows=$data['album'] ?? [];
        $ids=array_filter(array_column($rows,'id'));
        if(count($ids)!==count(array_unique($ids)))throw \Illuminate\Validation\ValidationException::withMessages(['album'=>'Each photo can appear only once.']);
        $uploads=[];foreach($rows as $index=>$row)if($request->hasFile("album.$index.image"))$uploads[$index]=$request->file("album.$index.image");
        HomeAlbum::save($rows,$uploads,$request->boolean('album_enabled'));
        return redirect(url('/admin/config').'#ui-controls')->with('home_album_saved',true);
    }
}
