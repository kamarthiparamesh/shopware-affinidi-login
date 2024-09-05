<?php declare(strict_types=1);

namespace AffinidiLoginPlugin;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

//Unable to resolove the other composer pkgs, so importing the autoloader explicitly
require __DIR__ . '/../vendor/autoload.php';

class AffinidiLoginPlugin extends Plugin
{
    public function install(InstallContext $installContext): void
    {
    }

    public function update(UpdateContext $updateContext): void
    {
    }
}
