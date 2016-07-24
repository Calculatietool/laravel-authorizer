<?php

namespace CalculatieTool\Authorizer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CalculatieToolExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'calculatietool', __NAMESPACE__.'\Provider'
        );
    }
}
