<?php

declare(strict_types=1);

namespace Conia\Core\View;

use Conia\Chuck\Exception\HttpBadRequest;
use Conia\Chuck\Request;
use Conia\Core\Config;
use Conia\Core\Middleware\Permission;
use Conia\Core\Util\Password;
use Conia\Quma\Database;

class User
{
    public function __construct(protected readonly Database $db)
    {
    }

    #[Permission('authenticated')]
    public function list()
    {
    }

    #[Permission('authenticated')]
    public function profile(Request $request): array
    {
        $usr = $request->get('session')->authenticatedUserId();
        $user = $this->db->users->get(['usr' => $usr])->one();

        if ($user['data']) {
            $data = json_decode($user['data'], true);
            $name = $data['name'] ?? '';
        } else {
            $name = '';
        }

        error_log(print_r($user, true));

        return [
            'uid' => $user['uid'],
            'username' => $user['username'],
            'email' => $user['email'],
            'name' => $name,
        ];
    }

    #[Permission('authenticated')]
    public function saveProfile(Request $request, Config $config): array
    {
        $data = $request->json();

        $usr = $request->get('session')->authenticatedUserId();
        $user = $this->db->users->get(['usr' => $usr])->one();
        $user['data'] = json_decode($user['data'], true);

        if ($data['uid'] !== $user['uid']) {
            throw HttpBadRequest::withPayload(['error' => 'Falsche uid']);
        }

        // E-Mail
        $email = trim($data['email'] ?? '');

        if ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw HttpBadRequest::withPayload(['error' => 'Die E-Mail-Adresse ist ungültig!']);
            }
        } else {
            throw HttpBadRequest::withPayload(['error' => 'Die E-Mail-Adresse muss angegeben werden!']);
        }

        if (strtolower($email) !== strtolower($user['email'])) {
            $existing = $this->db->users->get(['login' => $email])->one();

            if ($existing) {
                throw HttpBadRequest::withPayload(['error' => 'Die E-Mail-Adresse ist bereits vergeben']);
            }
        }

        // User name
        $username = trim($data['username'] ?? '');

        if ($username) {
            if ($username !== ($user['username'] ?? null) && strlen($username) > 64) {
                throw HttpBadRequest::withPayload(['error' => 'Der Benutzername ist zu lang']);
            }
        } else {
            $username = $user['username'] ?? null;
        }

        // Full name
        $name = trim($data['name'] ?? '');

        if ($name) {
            if ($name !== ($user['data']['name'] ?? '') && strlen($name) > 64) {
                throw HttpBadRequest::withPayload(['error' => 'Der vollständige Name ist zu lang']);
            }
        } else {
            $name = $user['data']['name'] ?? null;
        }

        // Password
        $pw = trim($data['password'] ?? '');

        if ($pw) {
            $passwordUtil = Password::fromConfig($config);

            if (!$passwordUtil->strongEnough($pw)) {
                throw HttpBadRequest::withPayload(['error' => 'Das Passwort ist zu schwach']);
            }

            if (trim($data['password']) !== trim($data['passwordRepeat'])) {
                throw HttpBadRequest::withPayload(['error' => 'Die neuen Passwörder stimmen nicht überein']);
            }

            $pwHash = $passwordUtil->hash($pw);
        } else {
            $pwHash = $user['pwhash'];
        }

        $this->db->users->save([
            'usr' => $usr,
            'email' => $email,
            'username' => $username,
            'data' => ['name' => $name],
            'pwhash' => $pwHash,
            'editor' => $usr,
        ])->run();

        return ['success' => true];
    }

    #[Permission('authenticated')]
    public function save(string $uid)
    {
    }

    #[Permission('authenticated')]
    public function create()
    {
    }
}
