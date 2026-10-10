<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

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
use Carbon\Carbon;

use App\Models\User;
use App\Models\Admin;
use App\Models\Button;
use App\Models\Link;
use App\Models\Page;
use App\Models\UserData;

class AdminController extends Controller
{
  //Statistics of the number of clicks and links
  public function index()
  {
    $userId = Auth::user()->id;
    $littlelink_name = Auth::user()->littlelink_name;
    $links = Link::where("user_id", $userId)->select("link")->count();
    $clicks = Link::where("user_id", $userId)->sum("click_number");

    $userNumber = User::count();
    $siteLinks = Link::count();
    $siteClicks = Link::sum("click_number");

    $users = User::select(
      "id",
      "name",
      "email",
      "created_at",
      "updated_at",
    )->get();
    $lastMonthCount = $users
      ->where("created_at", ">=", Carbon::now()->subDays(30))
      ->count();
    $lastWeekCount = $users
      ->where("created_at", ">=", Carbon::now()->subDays(7))
      ->count();
    $last24HrsCount = $users
      ->where("created_at", ">=", Carbon::now()->subHours(24))
      ->count();
    $updatedLast30DaysCount = $users
      ->where("updated_at", ">=", Carbon::now()->subDays(30))
      ->count();
    $updatedLast7DaysCount = $users
      ->where("updated_at", ">=", Carbon::now()->subDays(7))
      ->count();
    $updatedLast24HrsCount = $users
      ->where("updated_at", ">=", Carbon::now()->subHours(24))
      ->count();

    $links = Link::where("user_id", $userId)->select("link")->count();
    $clicks = Link::where("user_id", $userId)->sum("click_number");
    $topLinks = Link::where("user_id", $userId)
      ->orderby("click_number", "desc")
      ->whereNotNull("link")
      ->where("link", "<>", "")
      ->take(5)
      ->get();

    $pageStats = [
      "visitors" => [
        "all" => visits("App\Models\User", $littlelink_name)->count(),
        "day" => visits("App\Models\User", $littlelink_name)
          ->period("day")
          ->count(),
        "week" => visits("App\Models\User", $littlelink_name)
          ->period("week")
          ->count(),
        "month" => visits("App\Models\User", $littlelink_name)
          ->period("month")
          ->count(),
        "year" => visits("App\Models\User", $littlelink_name)
          ->period("year")
          ->count(),
      ],
      "os" => visits("App\Models\User", $littlelink_name)->operatingSystems(),
      "referers" => visits("App\Models\User", $littlelink_name)->refs(),
      "countries" => visits("App\Models\User", $littlelink_name)->countries(),
    ];

    return view("panel/index", [
      "lastMonthCount" => $lastMonthCount,
      "lastWeekCount" => $lastWeekCount,
      "last24HrsCount" => $last24HrsCount,
      "updatedLast30DaysCount" => $updatedLast30DaysCount,
      "updatedLast7DaysCount" => $updatedLast7DaysCount,
      "updatedLast24HrsCount" => $updatedLast24HrsCount,
      "toplinks" => $topLinks,
      "links" => $links,
      "clicks" => $clicks,
      "pageStats" => $pageStats,
      "littlelink_name" => $littlelink_name,
      "links" => $links,
      "clicks" => $clicks,
      "siteLinks" => $siteLinks,
      "siteClicks" => $siteClicks,
      "userNumber" => $userNumber,
    ]);
  }

  // Users page
  public function users()
  {
    return view("panel/users");
  }

  // Send test mail
  public function SendTestMail(Request $request)
  {
    try {
      $userId = auth()->id();
      $user = User::findOrFail($userId);

      Mail::send("auth.test", ["user" => $user], function ($message) use (
        $user,
      ) {
        $message->to($user->email)->subject("Test Email");
      });

      return redirect()
        ->route("showConfig")
        ->with("success", "Test email sent successfully!");
    } catch (\Exception $e) {
      return redirect()
        ->route("showConfig")
        ->with("fail", "Failed to send test email.");
    }
  }

  //Block user
  public function blockUser(request $request)
  {
    $id = $request->id;
    $status = $request->block;

    if ($status == "yes") {
      $block = "no";
    } elseif ($status == "no") {
      $block = "yes";
    }

    User::where("id", $id)->update(["block" => $block]);

    return redirect("admin/users/all");
  }

  //Verify user
  public function verifyCheckUser(request $request)
  {
    $id = $request->id;
    $status = $request->verify;

    if ($status == "vip") {
      $verify = "vip";
      UserData::saveData($id, "checkmark", true);
    } elseif ($status == "user") {
      $verify = "user";
    }

    User::where("id", $id)->update(["role" => $verify]);

    return redirect(url("u") . "/" . $id);
  }

  //Verify or un-verify users emails
  public function verifyUser(request $request)
  {
    $id = $request->id;
    $status = $request->verify;

    if ($status == "true") {
      $verify = "0000-00-00 00:00:00";
    } else {
      $verify = null;
    }

    User::where("id", $id)->update(["email_verified_at" => $verify]);
  }

  //Create new user from the Admin Panel
  public function createNewUser()
  {
    function random_str(
      int $length = 64,
      string $keyspace = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ",
    ): string {
      if ($length < 1) {
        throw new \RangeException("Length must be a positive integer");
      }
      $pieces = [];
      $max = mb_strlen($keyspace, "8bit") - 1;
      for ($i = 0; $i < $length; ++$i) {
        $pieces[] = $keyspace[random_int(0, $max)];
      }
      return implode("", $pieces);
    }

    $names = User::pluck("name")->toArray();

    $adminCreatedNames = array_filter($names, function ($name) {
      return strpos($name, "Admin-Created-") === 0;
    });

    $numbers = array_map(function ($name) {
      return (int) str_replace("Admin-Created-", "", $name);
    }, $adminCreatedNames);

    $maxNumber = !empty($numbers) ? max($numbers) : 0;
    $newNumber = $maxNumber + 1;

    $domain = parse_url(url(""), PHP_URL_HOST);
    $domain = $domain == "localhost" ? "example.com" : $domain;

    $user = User::create([
      "name" => "Admin-Created-" . $newNumber,
      "email" => strtolower(random_str(8)) . "@" . $domain,
      "password" => Hash::make(random_str(32)),
      "role" => "user",
      "block" => "no",
    ]);

    return redirect("admin/edit-user/" . $user->id);
  }

  //Delete existing user
  public function deleteUser(request $request)
  {
    $id = $request->id;

    Link::where("user_id", $id)->delete();

    Schema::disableForeignKeyConstraints();

    $user = User::find($id);
    $user->forceDelete();

    Schema::enableForeignKeyConstraints();

    return redirect("admin/users/all");
  }

  //Delete existing user with POST request
  public function deleteTableUser(request $request)
  {
    $id = $request->id;

    Link::where("user_id", $id)->delete();

    Schema::disableForeignKeyConstraints();

    $user = User::find($id);
    $user->forceDelete();

    Schema::enableForeignKeyConstraints();
  }

  //Show user to edit
  public function showUser(request $request)
  {
    $id = $request->id;

    $data["user"] = collect([User::findOrFail($id)]);

    return view("panel/edit-user", $data);
  }

  //Show link, click number, up link in links page
  public function showLinksUser(request $request)
  {
    $id = $request->id;

    $data["user"] = User::where("id", $id)->get();

    $data["links"] = Link::select(
      "id",
      "link",
      "title",
      "order",
      "click_number",
      "up_link",
      "links.button_id",
    )
      ->where("user_id", $id)
      ->orderBy("up_link", "asc")
      ->orderBy("order", "asc")
      ->paginate(10);
    return view("panel/links", $data);
  }

  //Delete link
  public function deleteLinkUser(request $request)
  {
    $linkId = $request->id;

    Link::where("id", $linkId)->delete();

    return back();
  }

  //Save user edit
  public function editUser(Request $request)
  {
    $id = $request->id;
    $user = User::findOrFail($id);
    $oldRole = $user->role;

    $data = $request->validate([
      "name" => "required|string|max:255",
      "email" => "required|email|max:255|unique:users,email," . $id,
      "password" => "nullable|min:8",
      "littlelink_name" => "required|string|max:255|regex:/^[A-Za-z0-9._-]+$/|unique:users,littlelink_name," . $id,
      "littlelink_description" => "nullable|string|max:10000",
      "role" => "required|in:user,vip,admin",
      "save_action" => "nullable|in:stay,exit",
      "theme" => "nullable|string|max:255",
      "image" => "nullable|image|mimes:jpeg,jpg,png,webp|max:2048",
      "background" => "nullable|image|mimes:jpeg,jpg,png,webp,gif|max:4096",
      "show_checkmark" => "nullable|boolean",
      "links_new_tab" => "nullable|boolean",
      "vip_badge_enabled" => "nullable|boolean",
      "vip_headline" => "nullable|string|max:220",
      "vip_message" => "nullable|string|max:800",
      "vip_cta_label" => "nullable|string|max:80",
      "vip_cta_url" => "nullable|url|max:2048",
    ]);

    $updates = [
      "name" => $data["name"],
      "email" => $data["email"],
      "littlelink_name" => $data["littlelink_name"],
      "littlelink_description" => $data["littlelink_description"] ?? null,
      "role" => $data["role"],
      "theme" => $data["theme"] ?? "default",
    ];
    if (!empty($data["password"])) {
      $updates["password"] = Hash::make($data["password"]);
    }
    // This admin-only route supplies an explicit, validated field list.
    // Keep role guarded on User for registration and other public writes.
    $user->forceFill($updates)->save();

    UserData::saveData($id, "checkmark", $request->boolean("show_checkmark"));
    UserData::saveData($id, "links-new-tab", $request->boolean("links_new_tab"));

    $vip = UserData::getData($id, "vip_profile");
    $vip = is_array($vip) ? $vip : [];
    $vipEnabled = $request->boolean("vip_badge_enabled") && $data["role"] === "vip";
    if ($vipEnabled && empty($vip["granted_at"])) {
      $vip["granted_at"] = now()->toIso8601String();
    }
    $vip["enabled"] = $vipEnabled;
    $vip["headline"] = trim((string)($data["vip_headline"] ?? ""));
    $vip["message"] = trim((string)($data["vip_message"] ?? ""));
    $vip["cta_label"] = trim((string)($data["vip_cta_label"] ?? ""));
    $vip["cta_url"] = $data["vip_cta_url"] ?? "";
    if ($oldRole !== "vip" && $data["role"] === "vip" && empty($vip["granted_at"])) {
      $vip["granted_at"] = now()->toIso8601String();
    }
    UserData::saveData($id, "vip_profile", $vip);

    if ($request->hasFile("image")) {
      while (findAvatar($id) !== "error.error") {
        $avatar = findAvatar($id);
        if (is_file(base_path($avatar))) {
          File::delete(base_path($avatar));
        } else {
          break;
        }
      }
      $image = $request->file("image");
      $image->move(base_path("assets/img"), $id . "_" . time() . "." . $image->extension());
    }

    if ($request->hasFile("background")) {
      while (findBackground($id) !== "error.error") {
        $path = base_path("assets/img/background-img/" . findBackground($id));
        if (is_file($path)) {
          File::delete($path);
        } else {
          break;
        }
      }
      $background = $request->file("background");
      $background->move(
        base_path("assets/img/background-img/"),
        $id . "_" . time() . "." . $background->extension()
      );
    }

    $destination = ($data["save_action"] ?? "stay") === "exit"
      ? route('showUsers')
      : route('showUser', ['id' => $user->id]);
    return redirect($destination)->with("success", "User updated.");
  }

  //Show site pages to edit
  public function showSitePage()
  {
    $data["pages"] = Page::select(
      "terms",
      "privacy",
      "contact",
      "register",
    )->get();
    return view("panel/pages", $data);
  }

  //Save site pages
  public function editSitePage(request $request)
  {
    $terms = $request->terms;
    $privacy = $request->privacy;
    $contact = $request->contact;
    $register = $request->register;

    Page::first()->update([
      "terms" => $terms,
      "privacy" => $privacy,
      "contact" => $contact,
      "register" => $register,
    ]);

    return back();
  }

  // Save presentation overrides without rewriting the advanced PHP configuration.
  public function editHomeUi(Request $request)
  {
    $buttons = config('advanced-config.buttons', []);
    $rules = [
      'buttons' => 'required|array|size:' . count($buttons),
      'preview_copy' => 'nullable|array',
      'preview_copy.title' => 'nullable|string|max:120',
      'preview_copy.tagline' => 'nullable|string|max:200',
      'preview_copy.description' => 'nullable|string|max:10000',
      'preview_copy.description_format' => 'nullable|in:text,html',
    ];
    foreach ($buttons as $index => $button) {
      $rules["buttons.$index.title"] = 'required|string|max:100';
      $rules["buttons.$index.link"] = ['required', 'url', 'max:2048', 'regex:/^https?:\/\//i'];
    }
    $data = $request->validate($rules);
    $clean = [];
    foreach ($buttons as $index => $button) {
      $clean[] = ['title' => $data['buttons'][$index]['title'], 'link' => $data['buttons'][$index]['link']];
    }
    $copy = \App\Support\HomeUi::previewCopy();
    foreach (['title', 'tagline', 'description'] as $key) {
      if (array_key_exists($key, $data['preview_copy'] ?? [])) {
        $copy[$key] = $data['preview_copy'][$key] ?? '';
      }
    }
    if (array_key_exists('description', $data['preview_copy'] ?? [])) {
      $copy['description_format'] = $data['preview_copy']['description_format'] ?? 'text';
      if ($copy['description_format'] === 'html') $copy['description'] = \App\Support\RichText::render($copy['description']);
    }
    if (!\Illuminate\Support\Facades\Storage::disk('local')->put('home-ui.json', json_encode(['buttons' => $clean, 'preview_copy' => $copy], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
      throw new \RuntimeException('Unable to save homepage links. Check storage permissions.');
    }
    return redirect(url('/admin/config') . '#ui-controls')->with('home_ui_saved', true);
  }

  //Show home message for edit
  public function showSite()
  {
    $message = Page::select("home_message")->first();
    return view("panel/site", $message);
  }

  //Save home message, logo and favicon
  public function editSite(request $request)
  {
    $data = $request->validate(['message' => 'nullable|string|max:20000']);
    $message = \App\Support\RichText::render($data['message'] ?? '');
    $logo = $request->file("image");
    $icon = $request->file("icon");

    Page::first()->update(["home_message" => $message]);

    if (!empty($logo)) {
      // Delete existing image
      $path = findFile("avatar");
      $path = base_path("/assets/linkstack/images/" . $path);

      // Delete existing image
      if (File::exists($path)) {
        File::delete($path);
      }

      $logo->move(
        base_path("/assets/linkstack/images/"),
        "avatar" . "_" . time() . "." . $request->file("image")->extension(),
      );
    }

    if (!empty($icon)) {
      // Delete existing image
      $path = findFile("favicon");
      $path = base_path("/assets/linkstack/images/" . $path);

      // Delete existing image
      if (File::exists($path)) {
        File::delete($path);
      }

      $icon->move(
        base_path("/assets/linkstack/images/"),
        "favicon" . "_" . time() . "." . $request->file("icon")->extension(),
      );
    }
    return back()->with('site_saved', true);
  }

  //Delete avatar
  public function delAvatar()
  {
    $path = findFile("avatar");
    $path = base_path("/assets/linkstack/images/" . $path);

    // Delete existing image
    if (File::exists($path)) {
      File::delete($path);
    }

    return back();
  }

  //Delete favicon
  public function delFavicon()
  {
    // Delete existing image
    $path = findFile("favicon");
    $path = base_path("/assets/linkstack/images/" . $path);

    // Delete existing image
    if (File::exists($path)) {
      File::delete($path);
    }

    return back();
  }

  //View footer page: terms
  public function pagesTerms(Request $request)
  {
    $name = "terms";

    try {
      $data["page"] = Page::select($name)->first();
    } catch (Exception $e) {
      return abort(404);
    }

    return view("pages", ["data" => $data, "name" => $name]);
  }

  //View footer page: privacy
  public function pagesPrivacy(Request $request)
  {
    $name = "privacy";

    try {
      $data["page"] = Page::select($name)->first();
    } catch (Exception $e) {
      return abort(404);
    }

    return view("pages", ["data" => $data, "name" => $name]);
  }

  //View footer page: contact
  public function pagesContact(Request $request)
  {
    $name = "contact";

    try {
      $data["page"] = Page::select($name)->first();
    } catch (Exception $e) {
      return abort(404);
    }

    return view("pages", ["data" => $data, "name" => $name]);
  }

  //Statistics of the number of clicks and links
  public function phpinfo()
  {
    return view("panel/phpinfo");
  }

  //Shows config file editor page
  public function showFileEditor(request $request)
  {
    return redirect("/admin/config");
  }

  //Saves advanced config
  public function editAC(request $request)
  {
    if ($request->ResetAdvancedConfig == "RESET_DEFAULTS") {
      copy(
        base_path("storage/templates/advanced-config.php"),
        base_path("config/advanced-config.php"),
      );
    } else {
      file_put_contents("config/advanced-config.php", $request->AdvancedConfig);
    }

    return redirect("/admin/config#2");
  }

  //Saves .env config
  public function editENV(request $request)
  {
    $config = $request->altConfig;

    file_put_contents(".env", $config);

    return Redirect("/admin/config?alternative-config");
  }

  //Shows config file editor page
  public function showBackups(request $request)
  {
    return view("/panel/backups");
  }

  //Delete custom theme
  public function deleteTheme(request $request)
  {
    $del = $request->deltheme;

    if (empty($del)) {
      echo '<script type="text/javascript">';
      echo 'alert("No themes to delete!");';
      echo 'window.location.href = "../studio/theme";';
      echo "</script>";
    } else {
      $folderName = base_path() . "/themes/" . $del;

      function removeFolder($folderName)
      {
        if (File::exists($folderName)) {
          File::deleteDirectory($folderName);
          return true;
        }

        return false;
      }

      removeFolder($folderName);

      return Redirect("/admin/theme");
    }
  }

  // Update themes
  public function updateThemes()
  {
    if ($handle = opendir("themes")) {
      while (false !== ($entry = readdir($handle))) {
        $configFile = base_path('themes/'.$entry.'/config.php');
        $themeConfig = is_file($configFile) ? include $configFile : [];
        if (($themeConfig['update_channel'] ?? '') === 'repository') {
          continue;
        }
        if (file_exists(base_path("themes") . "/" . $entry . "/readme.md")) {
          $text = file_get_contents(
            base_path("themes") . "/" . $entry . "/readme.md",
          );
          $pattern = "/Theme Version:.*/";
          preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
          if (!count($matches)) {
            continue;
          }
          $verNr = substr($matches[0][0], 15);
        }

        $themeVe = null;

        if ($entry != "." && $entry != "..") {
          if (file_exists(base_path("themes") . "/" . $entry . "/readme.md")) {
            if (
              !strpos(
                file_get_contents(
                  base_path("themes") . "/" . $entry . "/readme.md",
                ),
                "Source code:",
              )
            ) {
              $hasSource = false;
            } else {
              $hasSource = true;

              $text = file_get_contents(
                base_path("themes") . "/" . $entry . "/readme.md",
              );
              $pattern = "/Source code:.*/";
              preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
              $sourceURL = substr($matches[0][0], 13);

              $replaced = str_replace(
                "https://github.com/",
                "https://raw.githubusercontent.com/",
                trim($sourceURL),
              );
              $replaced = $replaced . "/main/readme.md";

              if (strpos($sourceURL, "github.com")) {
                ini_set("user_agent", "Mozilla/4.0 (compatible; MSIE 6.0)");
                try {
                  $textGit = file_get_contents($replaced);
                  $patternGit = "/Theme Version:.*/";
                  preg_match(
                    $patternGit,
                    $textGit,
                    $matches,
                    PREG_OFFSET_CAPTURE,
                  );
                  $sourceURLGit = substr($matches[0][0], 15);
                  $Vgitt = "v" . $sourceURLGit;
                  $verNrv = "v" . $verNr;
                } catch (Exception $ex) {
                  $themeVe = "error";
                  $Vgitt = null;
                  $verNrv = null;
                }

                if (trim($Vgitt) > trim($verNrv)) {
                  $fileUrl =
                    trim($sourceURL) .
                    "/archive/refs/tags/" .
                    trim($Vgitt) .
                    ".zip";

                  file_put_contents(
                    base_path("themes/theme.zip"),
                    fopen($fileUrl, "r"),
                  );

                  $zip = new ZipArchive();
                  $zip->open(base_path() . "/themes/theme.zip");
                  $zip->extractTo(base_path("themes"));
                  $zip->close();
                  unlink(base_path() . "/themes/theme.zip");

                  $folder = base_path("themes");
                  $regex = "/[0-9.-]/";
                  $files = scandir($folder);

                  foreach ($files as $file) {
                    if ($file !== "." && $file !== "..") {
                      if (preg_match($regex, $file)) {
                        $new_file = preg_replace($regex, "", $file);
                        File::copyDirectory(
                          $folder . "/" . $file,
                          $folder . "/" . $new_file,
                        );
                        $dirname = $folder . "/" . $file;
                        if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
                          system(
                            "rmdir " . escapeshellarg($dirname) . " /s /q",
                          );
                        } else {
                          system("rm -rf " . escapeshellarg($dirname));
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return Redirect("/studio/theme");
  }

  private function persistConfigToggle(string $key, string $value): bool
  {
    $toggleKeys = [
      "ALLOW_REGISTRATION",
      "REGISTER_AUTH",
      "MANUAL_USER_VERIFICATION",
      "FORCE_HTTPS",
      "HIDE_VERIFICATION_CHECKMARK",
      "ENABLE_REPORT_ICON",
      "NOTIFY_EVENTS",
      "NOTIFY_UPDATES",
      "ENABLE_BUTTON_EDITOR",
      "USE_THEME_PREVIEW_IFRAME",
      "ALLOW_CUSTOM_BACKGROUNDS",
      "ENABLE_ADMIN_BAR_USERS",
      "ALLOW_USER_HTML",
      "ALLOW_CUSTOM_CODE_IN_THEMES",
      "ENABLE_THEME_UPDATER",
      "ALLOW_USER_EXPORT",
      "ALLOW_USER_IMPORT",
      "JOIN_BETA",
      "SKIP_UPDATE_BACKUP",
      "CUSTOM_META_TAGS",
      "ENABLE_SOCIAL_LOGIN",
      "FORCE_ROUTE_HTTPS",
      "DISPLAY_FOOTER",
      "DISPLAY_CREDIT",
      "DISPLAY_CREDIT_FOOTER",
      "DISPLAY_FOOTER_HOME",
      "DISPLAY_FOOTER_TERMS",
      "DISPLAY_FOOTER_PRIVACY",
      "DISPLAY_FOOTER_CONTACT",
    ];

    if (!in_array($key, $toggleKeys, true)) {
      return false;
    }

    $envPath = base_path(".env");
    if (!is_file($envPath) || !is_readable($envPath) || !is_writable($envPath)) {
      return false;
    }

    $contents = file_get_contents($envPath);
    if ($contents === false) {
      return false;
    }

    $line = $key . "=" . $value;
    $pattern = "/^" . preg_quote($key, "/") . "\\s*=.*$/m";
    $updated = preg_replace($pattern, $line, $contents, -1, $count);

    if ($updated === null) {
      return false;
    }

    if ($count === 0) {
      $updated = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
    }

    $tempPath = $envPath . ".pinkkiss.tmp";
    if (file_put_contents($tempPath, $updated, LOCK_EX) === false) {
      return false;
    }

    @chmod($tempPath, fileperms($envPath) & 0777);
    if (!@rename($tempPath, $envPath)) {
      @unlink($tempPath);
      if (file_put_contents($envPath, $updated, LOCK_EX) === false) {
        return false;
      }
    }

    clearstatcache(true, $envPath);

    $verify = file_get_contents($envPath);
    if ($verify === false || !preg_match("/^" . preg_quote($key, "/") . "\\s*=\\s*" . preg_quote($value, "/") . "\\s*$/m", $verify)) {
      return false;
    }

    return true;
  }

  //Shows config file editor page
  public function showConfig(request $request)
  {
    return view("/panel/config-editor");
  }

  //Shows config file editor page
  public function editConfig(request $request)
  {
    $type = $request->type;
    $entry = $request->entry;
    $value = $request->value;

    if ($type === "toggle") {
      $value = $request->boolean("toggle") ? "true" : "false";
      if (!$this->persistConfigToggle($entry, $value)) {
        return Redirect("/admin/config")->with("config_save_error", $entry);
      }
    } elseif ($type === "toggle2") {
      $value = $request->boolean("toggle") ? "verified" : "auth";
      if (!$this->persistConfigToggle($entry, $value)) {
        return Redirect("/admin/config")->with("config_save_error", $entry);
      }
    } elseif ($type === "text") {
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, '"' . $value . '"');
      }
    } elseif ($type === "debug") {
      if ($request->toggle != "") {
        if (EnvEditor::keyExists("APP_DEBUG")) {
          EnvEditor::editKey("APP_DEBUG", "true");
        }
        if (EnvEditor::keyExists("APP_ENV")) {
          EnvEditor::editKey("APP_ENV", "local");
        }
        if (EnvEditor::keyExists("LOG_LEVEL")) {
          EnvEditor::editKey("LOG_LEVEL", "debug");
        }
      } else {
        if (EnvEditor::keyExists("APP_DEBUG")) {
          EnvEditor::editKey("APP_DEBUG", "false");
        }
        if (EnvEditor::keyExists("APP_ENV")) {
          EnvEditor::editKey("APP_ENV", "production");
        }
        if (EnvEditor::keyExists("LOG_LEVEL")) {
          EnvEditor::editKey("LOG_LEVEL", "error");
        }
      }
    } elseif ($type === "register") {
      if ($request->toggle != "") {
        $register = "true";
      } else {
        $register = "false";
      }
      Page::first()->update(["register" => $register]);
    } elseif ($type === "smtp") {
      if ($request->toggle != "") {
        $value = "built-in";
      } else {
        $value = "smtp";
      }
      if (EnvEditor::keyExists("MAIL_MAILER")) {
        EnvEditor::editKey("MAIL_MAILER", $value);
      }

      if (EnvEditor::keyExists("MAIL_HOST")) {
        EnvEditor::editKey("MAIL_HOST", $request->MAIL_HOST);
      }
      if (EnvEditor::keyExists("MAIL_PORT")) {
        EnvEditor::editKey("MAIL_PORT", $request->MAIL_PORT);
      }
      if (EnvEditor::keyExists("MAIL_USERNAME")) {
        EnvEditor::editKey(
          "MAIL_USERNAME",
          '"' . $request->MAIL_USERNAME . '"',
        );
      }
      if (EnvEditor::keyExists("MAIL_PASSWORD")) {
        EnvEditor::editKey(
          "MAIL_PASSWORD",
          '"' . $request->MAIL_PASSWORD . '"',
        );
      }
      if (EnvEditor::keyExists("MAIL_ENCRYPTION")) {
        EnvEditor::editKey("MAIL_ENCRYPTION", $request->MAIL_ENCRYPTION);
      }
      if (EnvEditor::keyExists("MAIL_FROM_ADDRESS")) {
        EnvEditor::editKey("MAIL_FROM_ADDRESS", $request->MAIL_FROM_ADDRESS);
      }
    } elseif ($type === "homeurl") {
      if ($request->value == "default") {
        $value = "";
      } else {
        $value = '"' . $request->value . '"';
      }
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    } elseif ($type === "maintenance") {
      if ($request->toggle != "") {
        $value = "true";
      } else {
        $value = "false";
      }
      if (file_exists(base_path("storage/MAINTENANCE"))) {
        unlink(base_path("storage/MAINTENANCE"));
      }
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    } else {
      if (EnvEditor::keyExists($entry)) {
        EnvEditor::editKey($entry, $value);
      }
    }

    \Illuminate\Support\Facades\Artisan::call("optimize:clear");

    return Redirect("/admin/config")->with("config_saved", $entry);
  }

  //Shows theme editor page
  public function showThemes(request $request)
  {
    return view("/panel/theme");
  }

  //Removes impersonation if authenticated
  public function authAs(Request $request)
  {
    $userID = $request->id;
    $token = $request->token;

    $user = User::find($userID);

    if (!$user) {
      Auth::logout();
      return redirect("/login");
    }

    $userRememberToken = $user->remember_token;
    $sessionToken = $request->session()->get("display_auth_nav");

    if (
      !empty($token) &&
      !empty($userRememberToken) &&
      !empty($sessionToken) &&
      hash_equals($userRememberToken, $token) &&
      hash_equals($userRememberToken, $sessionToken)
    ) {
      $user->auth_as = null;
      $user->remember_token = null;
      $user->save();

      $request->session()->forget("display_auth_nav");

      Auth::loginUsingId($userID);

      return redirect("/admin/users/all");
    } else {
      Auth::logout();
      return redirect("/login");
    }
  }

  //Add impersonation
  public function authAsID(request $request)
  {
    $adminUser = User::whereNotNull("auth_as")->where("role", "admin")->first();

    if (!$adminUser) {
      $userID = $request->id;
      $id = Auth::user()->id;

      $user = User::find($id);

      $user->auth_as = $userID;
      $user->save();

      return redirect("dashboard");
    } else {
      return redirect("admin/users/all");
    }
  }

  //Show info about link
  public function redirectInfo(request $request)
  {
    $linkId = $request->id;

    if (empty($linkId)) {
      return abort(404);
    }

    $linkData = Link::find($linkId);
    $clicks = $linkData->click_number;

    if (empty($linkData)) {
      return abort(404);
    }

    function isValidLink($url)
    {
      $validPrefixes = ["http", "https", "ftp", "mailto", "tel", "news"];

      $pattern = "/^(" . implode("|", $validPrefixes) . "):/i";

      if (preg_match($pattern, $url) && strlen($url) <= 155) {
        return $url;
      } else {
        return "N/A";
      }
    }

    $link = isValidLink($linkData->link);

    $userID = $linkData->user_id;
    $userData = User::find($userID);

    return view("linkinfo", [
      "clicks" => $clicks,
      "linkID" => $linkId,
      "link" => $link,
      "id" => $userID,
      "userData" => $userData,
    ]);
  }
}
