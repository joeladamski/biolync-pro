<?php
namespace App\Console\Commands;

use App\Support\RuntimeMedia;
use Illuminate\Console\Command;

class ImportProfileMedia extends Command
{
    protected $signature = 'estate:import-profile-media {--from= : Application root or backup root containing assets/}';
    protected $description = 'Copy legacy profile photos, covers, galleries and backgrounds into persistent storage';

    public function handle(): int
    {
        $source = $this->option('from') ?: base_path();
        if (!is_dir($source)) { $this->error('Source directory not found.'); return 1; }
        $count = 0;
        foreach (RuntimeMedia::DIRECTORIES as $directory) {
            RuntimeMedia::path($directory);
            foreach (glob(rtrim($source,'/').'/'.$directory.'/*') ?: [] as $file) {
                $logical = $directory.'/'.basename($file);
                if (!is_file($file) || is_link($file) || !RuntimeMedia::allowed($logical)) continue;
                $target = RuntimeMedia::root().'/'.substr($logical, strlen('assets/'));
                if (is_file($target)) continue;
                $temp = tempnam(dirname($target), 'media-import-');
                if (!$temp || !copy($file, $temp)) { $this->error('Copy failed: '.basename($file)); return 1; }
                chmod($temp, 0640);
                if (!rename($temp, $target)) { unlink($temp); $this->error('Publish failed.'); return 1; }
                $count++;
            }
        }
        $this->info($count.' files imported. Existing persistent files retained.');
        $this->info('Persistent directory: '.RuntimeMedia::root());
        return 0;
    }
}
