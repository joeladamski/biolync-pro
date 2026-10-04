<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

use GeoSot\EnvEditor\Controllers\EnvController;
use GeoSot\EnvEditor\Exceptions\EnvException;
use GeoSot\EnvEditor\Helpers\EnvFileContentManager;
use GeoSot\EnvEditor\Helpers\EnvFilesManager;
use GeoSot\EnvEditor\Helpers\EnvKeysManager;
use GeoSot\EnvEditor\Facades\EnvEditor;
use GeoSot\EnvEditor\ServiceProvider;

use Auth;
use Exception;
use ZipArchive;
use Artisan;

use App\Models\User;
use App\Models\Admin;
use App\Models\Button;
use App\Models\Link;
use App\Models\Page;

class InstallerController extends Controller
{

    public function showInstaller(Request $request)
    {
        if (!$this->bootstrapAvailable()) {
            abort(404);
        }

        // A production bootstrap already has a configured database and .env.
        // Skip the legacy language/database mutation screens and begin at the
        // first-owner step. Query-string steps are retained for the shared
        // installer view and the post-owner finalization screen.
        if ($request->getQueryString() === null || $request->getQueryString() === '') {
            return redirect(url('/setup-owner?4'));
        }

        $allowedSteps = ['4', '5'];
        if (!in_array($request->getQueryString(), $allowedSteps, true)) {
            abort(404);
        }

        return view('installer/installer');
    }

    protected function bootstrapAvailable(): bool
    {
        return !File::exists(storage_path('app/ISINSTALLED'))
            && Schema::hasTable('users')
            && DB::table('users')->count() === 0;
    }

    public function db(request $request)
    {
        if($request->database == 'MySQL'){
            return redirect(url('?mysql'));
        }else{
            return redirect(url('?4'));
        }
    }

    public function createAdmin(request $request)
    {
        if (!$this->bootstrapAvailable()) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'handle' => 'required|string|max:50|unique:users,littlelink_name|regex:/^[\\p{L}0-9-_]+$/u',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $file = base_path('INSTALLERLOCK');
        if (!file_exists($file)) {
            File::put($file, '');
        }

        $user = DB::transaction(function () use ($request) {
            if (DB::table('users')->lockForUpdate()->count() !== 0) {
                abort(404);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'email_verified_at' => '0001-01-01 00:00:00',
                'password' => Hash::make($request->password),
                'littlelink_name' => $request->handle,
                'littlelink_description' => 'admin page',
                'block' => 'no',
            ]);

            $user->role = 'admin';
            $user->save();

            return $user;
        });

        if (EnvEditor::keyExists('ADMIN_EMAIL')) {
            EnvEditor::editKey('ADMIN_EMAIL', $user->email);
        } else {
            EnvEditor::addKey('ADMIN_EMAIL', $user->email);
        }

        Auth::login($user);

        return redirect(url('/setup-owner?5'));
    }

    public function mysql(request $request)
    {
        $DB_CONNECTION = 'mysql';
        $DB_HOST = $request->host;
        $DB_PORT = $request->port;
        $DB_DATABASE = $request->name;
        $DB_USERNAME = $request->username;
        $DB_PASSWORD = $request->password;

        if(EnvEditor::keyExists('DB_CONNECTION')){EnvEditor::editKey('DB_CONNECTION', $DB_CONNECTION);}else{EnvEditor::addKey('DB_CONNECTION', $DB_CONNECTION);}
        if(EnvEditor::keyExists('DB_HOST')){EnvEditor::editKey('DB_HOST', $DB_HOST);}else{EnvEditor::addKey('DB_HOST', $DB_HOST);}
        if(EnvEditor::keyExists('DB_PORT')){EnvEditor::editKey('DB_PORT', $DB_PORT);}else{EnvEditor::addKey('DB_PORT', $DB_PORT);}
        if(EnvEditor::keyExists('DB_DATABASE')){EnvEditor::editKey('DB_DATABASE', $DB_DATABASE);}else{EnvEditor::addKey('DB_DATABASE', $DB_DATABASE);}
        if(EnvEditor::keyExists('DB_USERNAME')){EnvEditor::editKey('DB_USERNAME', $DB_USERNAME);}else{EnvEditor::addKey('DB_USERNAME', $DB_USERNAME);}
        if(EnvEditor::keyExists('DB_PASSWORD')){EnvEditor::editKey('DB_PASSWORD', $DB_PASSWORD);}else{EnvEditor::addKey('DB_PASSWORD', $DB_PASSWORD);}

        return redirect(url('mysql-test'));

    }

    public function mysqlTest(request $request)
    {
        try {Artisan::call('migrate');} catch (exception $e) {$failed = "true";}
        try {Artisan::call('db:seed --force');} catch (exception $e) {$failed = "true";}
        try {Artisan::call('db:seed --class="PageSeeder" --force');} catch (exception $e) {$failed = "true";}
        try {Artisan::call('db:seed --class="ButtonSeeder" --force');} catch (exception $e) {$failed = "true";}

        try {$users = DB::table('buttons')->count(); $failed = false;} catch (exception $e) {$failed = true;}

        if($failed == true){
            if(EnvEditor::keyExists('DB_CONNECTION')){EnvEditor::editKey('DB_CONNECTION', 'sqlite');}else{EnvEditor::addKey('DB_CONNECTION', 'sqlite');}
            if(EnvEditor::keyExists('DB_HOST')){EnvEditor::deleteKey('DB_HOST');}
            if(EnvEditor::keyExists('DB_PORT')){EnvEditor::deleteKey('DB_PORT');}
            if(EnvEditor::keyExists('DB_DATABASE')){EnvEditor::deleteKey('DB_DATABASE');}
            if(EnvEditor::keyExists('DB_USERNAME')){EnvEditor::deleteKey('DB_USERNAME');}
            if(EnvEditor::keyExists('DB_PASSWORD')){EnvEditor::deleteKey('DB_PASSWORD');}
            return redirect(url('?error'));
        }else{
            return redirect(url('?4'));
        }
    }

    public function options(request $request)
    {
        if (File::exists(storage_path('app/ISINSTALLED')) || !Schema::hasTable('users')) {
            abort(404);
        }

        $user = User::where('role', 'admin')->orderBy('created_at')->first();

        if (!$user || User::count() !== 1) {
            abort(404);
        }

        $llName = $user->littlelink_name;

        if($request->register == 'Yes'){
            if(EnvEditor::keyExists('ALLOW_REGISTRATION')){EnvEditor::editKey('ALLOW_REGISTRATION', 'true');}else{EnvEditor::addKey('ALLOW_REGISTRATION', 'true');}
        } else {
            if(EnvEditor::keyExists('ALLOW_REGISTRATION')){EnvEditor::editKey('ALLOW_REGISTRATION', 'false');}else{EnvEditor::addKey('ALLOW_REGISTRATION', 'false');}
        }

        if($request->verify == 'Yes'){$value = "verified";}else{$value = "auth";}
        if(EnvEditor::keyExists('REGISTER_AUTH')){EnvEditor::editKey('REGISTER_AUTH', $value);}else{EnvEditor::addKey('REGISTER_AUTH', $value);}

        if($request->page == 'No'){$value = "";}else{$value = '"' . $llName . '"';}
        if(EnvEditor::keyExists('HOME_URL')){EnvEditor::editKey('HOME_URL', $value);}

        if(EnvEditor::keyExists('APP_NAME')){EnvEditor::editKey('APP_NAME', '"' . $request->app . '"');}

        File::put(storage_path('app/ISINSTALLED'), '');

        if(file_exists(base_path("INSTALLING"))){unlink(base_path("INSTALLING"));}

        $file = base_path('INSTALLERLOCK');
        if (file_exists($file)) {
            unlink($file) or die('Cannot delete file: '.$file);
            sleep(1);
        }

        return redirect(url('dashboard'));
    }

    public function editConfigInstaller(request $request)
    {

        $type = $request->type;
        $entry = $request->entry;
        $value = $request->value;
        $value = '"' . $request->value . '"';

        if(EnvEditor::keyExists($entry)){EnvEditor::editKey($entry, $value);}

        return Redirect(url('dashboard'));
    }

}
