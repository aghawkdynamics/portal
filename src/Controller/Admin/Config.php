<?php
namespace App\Controller\Admin;

use App\Core\Config as CoreConfig;

class Config extends AdminController
{
    public function handle(): void
    {
        $this->render('admin/config/index', [
            'config' => $this->getConfig(),
        ]);
    }

    private function getConfig(): array
    {
        // Fetch the configuration settings from the database or config files
        return CoreConfig::all();
    }
}