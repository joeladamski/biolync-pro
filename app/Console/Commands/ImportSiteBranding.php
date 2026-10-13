<?php
namespace App\Console\Commands;

use App\Support\SiteBranding;
use Illuminate\Console\Command;

class ImportSiteBranding extends Command
{
    protected $signature = 'estate:import-branding {--from= : Directory containing existing avatar and favicon files}';
    protected $description = 'Copy estate branding outside the deployment root without replacing existing persistent branding';

    public function handle(): int
    {
        $source = $this->option('from') ?: base_path('assets/linkstack/images');
        if (!is_dir($source)) { $this->error('Source directory not found.'); return 1; }
        foreach (['avatar', 'favicon'] as $kind) {
            if (SiteBranding::path($kind, false)) { $this->info($kind.': already persistent; retained.'); continue; }
            $files = array_filter(glob(rtrim($source, '/').'/'.$kind.'*') ?: [], fn ($p) => is_file($p) && !is_link($p) && preg_match('/^'.$kind.'(?:_[a-zA-Z0-9]+)?\.(png|jpe?g|webp|gif)$/', basename($p)));
            usort($files, fn ($a,$b) => filemtime($b) <=> filemtime($a));
            if (!$files) { $this->warn($kind.': no source image found.'); continue; }
            SiteBranding::copy($kind, $files[0]);
            $this->info($kind.': imported into persistent storage.');
        }
        $this->info('Persistent directory: '.SiteBranding::directory());
        return 0;
    }
}
