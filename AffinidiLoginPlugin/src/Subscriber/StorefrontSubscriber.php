<?php declare(strict_types=1);

namespace AffinidiLoginPlugin\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\GenericPageLoadedEvent;

class StorefrontSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            GenericPageLoadedEvent::class => 'onGenericPageLoaded',
        ];
    }
    public function onGenericPageLoaded(GenericPageLoadedEvent $event): void
    {
        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');
        //dd($routeName);
        if ($routeName === 'frontend.account.login.page') {
            $page = $event->getPage();
            $page->assign(['customButton' => true]);
        }
    }

}
