<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserData;
use App\Support\AiDiscovery;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiDiscoveryController extends Controller
{
    private function document(string $text)
    {
        return response($text,200,['Content-Type'=>'text/plain; charset=UTF-8','Cache-Control'=>'no-store']);
    }

    public function site($full=false)
    {
        abort_unless(AiDiscovery::settings()['enabled'],404);
        return $this->document(AiDiscovery::siteText((bool)$full));
    }

    public function profile($littlelink,$full=false)
    {
        $user=User::where('littlelink_name',$littlelink)->first();
        abort_unless($user && AiDiscovery::publishedProfile($user->id),404);
        return $this->document(AiDiscovery::profileText($user,(bool)$full));
    }

    public function saveSite(Request $request)
    {
        $data=$request->validate(['enabled'=>'required|boolean','introduction'=>'nullable|string|max:2000','context'=>'nullable|string|max:5000','reset'=>'nullable|boolean']);
        AiDiscovery::saveSettings(['enabled'=>(bool)$data['enabled'],'introduction'=>$request->boolean('reset') ? '' : ($data['introduction'] ?? ''),'context'=>$request->boolean('reset') ? '' : ($data['context'] ?? '')]);
        return redirect(url('/admin/config').'#ai-discovery')->with('ai_success','AI Discovery settings saved.');
    }

    public function saveFeatured(Request $request)
    {
        $data=$request->validate(['featured_user_id'=>'nullable|integer|exists:users,id']);
        $user=empty($data['featured_user_id']) ? null : User::find($data['featured_user_id']);
        if($user && !AiDiscovery::publicUser($user))throw ValidationException::withMessages(['featured_user_id'=>'Choose an unblocked account with a public profile address.']);
        AiDiscovery::saveSettings(['featured_user_id'=>$user?->id]);
        return redirect(url('/admin/config').'#ui-controls')->with('home_ui_saved',true);
    }

    public function saveProfile(Request $request,$id)
    {
        $user=User::findOrFail($id);
        $data=$request->validate(['enabled'=>'required|boolean','summary'=>'nullable|string|max:2000','context'=>'nullable|string|max:5000','reset'=>'nullable|boolean']);
        UserData::saveData($user->id,'ai_discovery',['enabled'=>(bool)$data['enabled'],'summary'=>$request->boolean('reset') ? '' : ($data['summary'] ?? ''),'context'=>$request->boolean('reset') ? '' : ($data['context'] ?? '')]);
        return redirect(url('/admin/edit-user/'.$user->id).'#ai-discovery')->with('ai_success','Profile AI Discovery saved.');
    }
}
