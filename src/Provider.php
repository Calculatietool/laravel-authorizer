<?php

namespace CalculatieTool\Authorizer;

use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'CALCULATIETOOL';
    const HOST = 'https://app.calculatietool.com';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [''];

    /**
     * Get endpoint from config if set.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        $config_endpoint = config('services.calculatietool.endpoint');
        if (!is_null($config_endpoint)) {
            return $config_endpoint;
        }

        return self::HOST;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getEndpoint() . '/oauth2/authorize', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getEndpoint() . '/oauth2/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            $this->getEndpoint() . '/oauth2/rest/user?access_token=' . $token['access_token']
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'name' => $user['firstname'] . ' ' . $user['lastname'],
            'username' => $user['username'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'isadmin' => $user['isadmin'],
            'issuperuser' => $user['issuperuser'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($body)
    {
        return json_decode($body, true);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = $this->getAccessTokenResponse($this->getCode())
        ));

        unset($user->accessTokenResponseBody);
        unset($user->nickname);
        unset($user->avatar);

        $user->setToken(array_get($token, 'access_token'));
        $user->setRefreshToken(array_get($token, 'refresh_token'));
        $user->setExpiresIn(array_get($token, 'expires_in'));

        return $user;
    }
}
