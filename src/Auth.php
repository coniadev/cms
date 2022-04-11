<?php

declare(strict_types=1);

namespace Conia;

use \RuntimeException;
use Chuck\Database\DatabaseInterface;
use Chuck\Util\Time;
use Conia\Config;
use Conia\Request;
use Conia\Permissions;


class RememberDetails
{
    public function __construct(Token $token, int $expires)
    {
        $this->token = $token;
        $this->expires = $expires;
    }
}


class Auth
{
    protected Config $config;
    protected Session $session;
    protected Users $users;

    public function __construct(
        protected DatabaseInterface $db,
        protected Request $request,
    ) {
        $this->config = $request->getConfig();
        $this->session = $request->session();
        $this->user = new Users($db, $request);
    }

    protected function remember(string $userId): RememberDetails
    {
        $token = new Token($this->config->get('secret'));
        $expires = time() + $this->config->get('session')['expire'];

        $remembered = ($this->user->remember)([
            'hash' => $token->hash(),
            'user' => $userId,
            'expires' => Time::toIsoDateTime($expires),
        ])->run();

        if ($remembered) {
            return new RememberDetails($token, $expires);
        } else {
            throw new RuntimeException('Could not remember user');
        }
    }

    protected function login(string $userId, bool $remember): void
    {
        $session = $this->session;

        // regenerate the session id before setting the user id
        // to mitigate session fixation attack.
        $session->regenerate();
        $session->setUser($userId);

        if ($remember) {
            $details = $this->remember($userId);

            if ($details) {
                $session->remember(
                    $details->token,
                    $details->expires
                );
            };
        }
    }

    public function logout(): void
    {
        $session = $this->request->session;
        $session->forget();

        $hash = $this->getTokenHash();
        if ($hash) {
            $session->forgetRemembered();
        }
    }

    public function authenticate(
        string $login,
        string $password,
        bool $remember,
        bool $initSession,
    ): ?string {
        $user = $this->user->byLogin($login);

        if (!$user) {
            return null;
        }

        if (password_verify($password, $user['pwhash'])) {
            if ($initSession) {
                $this->login($user['uid'], $remember);
            }

            return $user['uid'];
        } else {
            return null;
        }
    }


    protected function getTokenHash(): ?string
    {
        $token = $this->request->session->getAuthToken();

        if ($token) {
            $hash = (new Token($this->config->get('secret'), $token))->hash();
            return $hash;
        }

        return null;
    }

    public function user(): ?array
    {
        static $user = false;

        if ($user !== false) {
            return $user;
        }

        // verify if user is logged in via cookie session
        $userId = self::$request->session->authenticatedUserId();

        if ($userId) {
            $user = $this->user->byId($userId);
            return $user;
        }

        $hash = $this->getTokenHash();
        if ($hash) {
            $user = $this->user->byLoginSession($hash);

            if ($user && !(strtotime($user['expires']) < time())) {
                $this->login($user['usr'], false);
                return $user;
            }
        }

        $user = null; // set static var
        return $user;
    }

    public function permissions(): array
    {
        $permissions = new Permissions($this->config);
        $user = $this->user();

        if ($user === null) {
            return [];
        }

        return $permissions->get($user['role']);
    }
}