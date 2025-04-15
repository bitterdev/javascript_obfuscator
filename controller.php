<?php

namespace Concrete\Package\JavascriptObfuscator;

use Concrete\Core\Package\Package;
use Concrete\Core\Page\Page;
use Concrete\Core\User\Group\GroupRepository;
use Concrete\Core\User\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Tholu\Packer\Packer;
use DOMDocument;
use DOMXPath;

class Controller extends Package
{
    protected string $pkgHandle = 'javascript_obfuscator';
    protected string $pkgVersion = '0.0.2';
    protected $appVersionRequired = '9.0.0';

    public function getPackageDescription(): string
    {
        return t('Javascript Obfuscator is a Concrete CMS add-on that hides your inline Javascript code by obfuscating it, improving security and protecting your source logic.');
    }

    public function getPackageName(): string
    {
        return t('Javascript Obfuscator');
    }

    public function on_start()
    {
        require_once('vendor/autoload.php');

        /** @var EventDispatcherInterface $eventDispatcher */
        /** @noinspection PhpUnhandledExceptionInspection */
        $eventDispatcher = $this->app->make(EventDispatcherInterface::class);

        $eventDispatcher->addListener('on_page_output', function ($event) {
            /** @var $event GenericEvent */
            $htmlCode = $event->getArgument('contents');

            $u = new User();

            /** @var GroupRepository $groupRepository */
            $groupRepository = $this->app->make(GroupRepository::class);
            $adminGroup = $groupRepository->getGroupByID(ADMIN_GROUP_ID);

            /** @var $c Page */
            $c = Page::getCurrentPage();

            if (!($u->isSuperUser() || (is_object($adminGroup) && $u->inGroup($adminGroup)) ||
                ($c instanceof Page && ($c->isEditMode())))) {

                $doc = new DOMDocument();
                @$doc->loadHTML($htmlCode);
                $xpath = new DOMXPath($doc);

                foreach ($xpath->query('//script') as $scriptNode) {
                    if (strlen($scriptNode->nodeValue) > 0) {
                        $packer = new Packer($scriptNode->nodeValue);
                        $scriptNode->nodeValue = $packer->pack();
                    }
                }

                $htmlCode = $doc->saveHTML();
            }

            $event->setArgument('contents', $htmlCode);
        });
    }
}