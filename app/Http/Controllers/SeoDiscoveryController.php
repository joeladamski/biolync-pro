<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserData;
use App\Support\SeoDiscovery;
use Illuminate\Http\Request;

class SeoDiscoveryController extends Controller
{
    public function saveSite(Request $request)
    {
        $data=$request->validate([
            'enabled'=>'required|boolean',
            'title'=>'nullable|string|max:120',
            'description'=>'nullable|string|max:500',
            'canonical_url'=>'nullable|url|max:500',
            'robots'=>'nullable|string|max:120',
            'og_title'=>'nullable|string|max:120',
            'og_description'=>'nullable|string|max:500',
            'og_image'=>'nullable|url|max:500',
            'schema_enabled'=>'required|boolean',
            'reset'=>'nullable|boolean',
        ]);
        $reset=$request->boolean('reset');
        SeoDiscovery::saveSettings([
            'enabled'=>(bool)$data['enabled'],
            'title'=>$reset ? '' : ($data['title'] ?? ''),
            'description'=>$reset ? '' : ($data['description'] ?? ''),
            'canonical_url'=>$reset ? '' : ($data['canonical_url'] ?? ''),
            'robots'=>$reset ? 'index,follow' : ($data['robots'] ?? 'index,follow'),
            'og_title'=>$reset ? '' : ($data['og_title'] ?? ''),
            'og_description'=>$reset ? '' : ($data['og_description'] ?? ''),
            'og_image'=>$reset ? '' : ($data['og_image'] ?? ''),
            'schema_enabled'=>(bool)$data['schema_enabled'],
        ]);
        return redirect(url('/admin/config').'#seo-discovery')->with('seo_success','SEO Discovery settings saved.');
    }

    public function saveProfile(Request $request,$id)
    {
        $user=User::findOrFail($id);
        $data=$request->validate([
            'indexable'=>'required|boolean',
            'title'=>'nullable|string|max:120',
            'description'=>'nullable|string|max:500',
            'canonical_url'=>'nullable|url|max:500',
            'robots'=>'nullable|string|max:120',
            'reset'=>'nullable|boolean',
        ]);
        $reset=$request->boolean('reset');
        UserData::saveData($user->id,'seo_discovery',[
            'indexable'=>(bool)$data['indexable'],
            'title'=>$reset ? '' : ($data['title'] ?? ''),
            'description'=>$reset ? '' : ($data['description'] ?? ''),
            'canonical_url'=>$reset ? '' : ($data['canonical_url'] ?? ''),
            'robots'=>$reset ? '' : ($data['robots'] ?? ''),
        ]);
        return redirect(url('/admin/edit-user/'.$user->id).'#seo-discovery')->with('seo_success','Profile SEO Discovery saved.');
    }
}
