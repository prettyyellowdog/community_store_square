<?php

namespace Concrete\Package\CommunityStoreSquare;

use Package;
use Whoops\Exception\ErrorException;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package
{
    protected $pkgHandle = 'community_store_square';
    protected $appVersionRequired = '8.4.0';
    protected $pkgVersion = '0.9.5';

    public function on_start()
    {
        $this->setupAutoloader();
    }

    public function getPackageDescription()
    {
        return t("Square Payment Method for Community Store");
    }

    public function getPackageName()
    {
        return t("Square Payment Method");
    }

    public function install()
    {
        $installed = Package::getInstalledHandles();
        if (!(is_array($installed) && in_array('community_store', $installed))) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        } else {
            $this->setupAutoloader();
            $pkg = parent::install();
            $pm = new PaymentMethod();
            $pm->add('community_store_square', 'Square', $pkg);
        }
    }
    public function uninstall()
    {
        $this->setupAutoLoader();
        $pm = PaymentMethod::getByHandle('community_store_square');
        if ($pm) {
            $pm->delete();
        }
        $pkg = parent::uninstall();
    }

    private function setupAutoloader()
    {
        if (file_exists($this->getPackagePath() . '/vendor')) {
            require_once $this->getPackagePath() . '/vendor/autoload.php';
        }
    }
}
