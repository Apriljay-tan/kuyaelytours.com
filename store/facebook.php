<?php
declare(strict_types=1);

// Facebook identities are independent of the legacy single-provider user columns.
// Google continues to use its existing login path and stored identity unchanged.
function store_facebook_db(): PDO
{
	$db = store_db();
	if (!$db) {
		throw new RuntimeException('Facebook account linking requires an available database.');
	}
	$db->exec('CREATE TABLE IF NOT EXISTS ke_social_identities (
		provider VARCHAR(40) NOT NULL,
		provider_id VARCHAR(190) NOT NULL,
		user_id VARCHAR(32) NOT NULL,
		PRIMARY KEY (provider, provider_id),
		UNIQUE (provider, user_id)
	)' . ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' ENGINE=InnoDB' : ''));
	return $db;
}

function store_facebook_identity_user(PDO $db, string $id): ?array
{
	$stmt = $db->prepare('SELECT u.* FROM ke_social_identities s JOIN ke_users u ON u.id=s.user_id WHERE s.provider=? AND s.provider_id=?');
	$stmt->execute(['facebook', $id]);
	$user = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($user) {
		return $user;
	}
	// Recognize existing Facebook users before their first identity-table migration.
	$stmt = $db->prepare('SELECT * FROM ke_users WHERE oauth_provider=? AND oauth_id=? LIMIT 2');
	$stmt->execute(['facebook', $id]);
	$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if (count($users) > 1) {
		throw new RuntimeException('Ambiguous legacy Facebook identity.');
	}
	return $users[0] ?? null;
}

function store_facebook_record_identity(PDO $db, array $user, string $id): void
{
	if (($user['oauth_provider'] ?? '') === 'facebook' && ($user['oauth_id'] ?? '') !== '' && (string) $user['oauth_id'] !== $id) {
		throw new RuntimeException('Account already has a different Facebook identity.');
	}
	$stmt = $db->prepare('SELECT provider_id FROM ke_social_identities WHERE provider=? AND user_id=?');
	$stmt->execute(['facebook', $user['id']]);
	$existing = $stmt->fetchColumn();
	if ($existing !== false) {
		if ((string) $existing !== $id) {
			throw new RuntimeException('Account already has a different Facebook identity.');
		}
		return;
	}
	$stmt = $db->prepare('INSERT INTO ke_social_identities (provider, provider_id, user_id) VALUES (?,?,?)');
	if (!$stmt->execute(['facebook', $id, $user['id']])) {
		throw new RuntimeException('Could not save Facebook account link.');
	}
}

function store_facebook_begin_completion(array $profile, string $next): void
{
	$_SESSION['facebook_completion'] = [
		'id' => (string) $profile['id'],
		'name' => (string) ($profile['name'] ?? ''),
		'email' => (string) ($profile['email'] ?? ''),
		'next' => store_safe_next($next, '/account/bookings.php'),
		'expires' => time() + 900,
	];
}

function store_facebook_pending(): ?array
{
	$pending = $_SESSION['facebook_completion'] ?? null;
	if (!is_array($pending) || (string) ($pending['id'] ?? '') === '' || (int) ($pending['expires'] ?? 0) <= time()) {
		unset($_SESSION['facebook_completion']);
		return null;
	}
	return $pending;
}

function store_login_facebook(array $profile): string
{
	return store_facebook_authenticate($profile, false);
}

function store_complete_facebook(string $email, string $password): string
{
	$pending = store_facebook_pending();
	if (!$pending) {
		return 'Facebook sign-in expired. Please start again.';
	}
	return store_facebook_authenticate([
		'id' => $pending['id'],
		'name' => $pending['name'],
		'email' => $email,
	], true, $password);
}

function store_facebook_authenticate(array $profile, bool $completing, string $password = ''): string
{
	$id = (string) ($profile['id'] ?? '');
	$email = strtolower(trim((string) ($profile['email'] ?? '')));
	$name = trim((string) ($profile['name'] ?? ''));
	store_fb_debug_secrets([$id, $email, $name, $password]);
	if ($id === '' || strlen($id) > 190) {
		store_fb_debug('[FAIL] FACEBOOK_ID_MISSING_OR_INVALID');
		return 'Could not read that Facebook account.';
	}
	$db = null;
	try {
		store_fb_debug_stage('DATABASE');
		$db = store_facebook_db();
		$db->beginTransaction();
		$user = store_facebook_identity_user($db, $id);
		store_fb_debug('OAUTH_USER_FOUND=' . ($user ? 'YES' : 'NO'));
		if (!$user) {
        $emailValid = $email !== ''
                && strlen($email) <= 190
                && filter_var($email, FILTER_VALIDATE_EMAIL);

        // If the user is manually completing/linking an account,
        // an email is still required.
        if ($completing && !$emailValid) {
                $db->rollBack();
                return 'Enter a valid email address.';
        }

        // Facebook may legitimately return no email.
        // A non-empty but invalid email should still be rejected.
        if ($email !== '' && !$emailValid) {
                $db->rollBack();
                return 'Could not read that Facebook account.';
        }

        $user = null;

        if ($emailValid) {
                $stmt = $db->prepare('SELECT * FROM ke_users WHERE email=?');
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                store_fb_debug('EXISTING_USER=' . ($user ? 'YES' : 'NO'));

                if ($user) {
                        // Never silently attach Facebook to an existing
                        // account merely because its email matches.
                        if (!$completing) {
                                $db->rollBack();
                                return 'complete_profile';
                        }

                        $signedIn = (string) ($_SESSION['store_user'] ?? '')
                                === (string) $user['id'];

                        if (!$signedIn) {
                                if (!store_login_allowed()) {
                                        $db->rollBack();
                                        return 'Please wait a few minutes and try again.';
                                }

                                $hash = (string) ($user['password_hash'] ?? '');

                                if ($hash === '' || !password_verify($password, $hash)) {
                                        store_login_fail();
                                        store_fb_debug('[FAIL] ACCOUNT_LINKING SIGN_IN_REQUIRED');
                                        $db->rollBack();

                                        return 'Sign in to your existing account first, or enter its correct password, to link Facebook.';
                                }
                        }

                        store_fb_debug('ACCOUNT_OWNERSHIP_CONFIRMED=YES');
                }
        } else {
                store_fb_debug('EXISTING_USER=NO');
        }

        // No linked/local user exists, so create the Facebook user.
        if (!$user) {
                $displayName = $name !== '' ? $name : 'Facebook User';

                $displayName = function_exists('mb_substr')
                        ? mb_substr($displayName, 0, 140)
                        : substr($displayName, 0, 140);

                $user = [
                        'id' => store_id(),
                        'name' => $displayName,
                        'email' => $emailValid ? $email : null,
                        'phone' => '',
                        'password_hash' => '',
                        'oauth_provider' => 'facebook',
                        'oauth_id' => $id,
                        'created' => store_now(),
                ];

                $stmt = $db->prepare(
                        'INSERT INTO ke_users
                        (id,name,email,phone,password_hash,oauth_provider,oauth_id,created)
                        VALUES (?,?,?,?,?,?,?,?)'
                );

                if (!$stmt->execute([
                        $user['id'],
                        $user['name'],
                        $user['email'],
                        $user['phone'],
                        $user['password_hash'],
                        $user['oauth_provider'],
                        $user['oauth_id'],
                        $user['created'],
                ])) {
                        throw new RuntimeException('Could not save Facebook account.');
                }

                store_fb_debug('FACEBOOK_ACCOUNT_CREATED=YES');
                store_fb_debug(
                        'ACCOUNT_EMAIL_PRESENT=' . ($emailValid ? 'YES' : 'NO')
                );
        }
}
		store_fb_debug_stage('ACCOUNT_LINKING');
		store_facebook_record_identity($db, $user, $id);
		$db->commit();
		store_fb_debug('DATABASE_SUCCESS=YES');
		store_fb_debug_stage('LOGIN_SESSION');
		if (!session_regenerate_id(true)) {
			store_fb_debug('[FAIL] LOGIN_SESSION');
			return 'Could not finish sign-in. Please try again.';
		}
		$_SESSION['store_user'] = $user['id'];
		unset($_SESSION['facebook_completion']);
		store_login_clear();
		store_fb_debug('LOGIN_SESSION_CREATED=YES');
		store_fb_debug_stage('AFTER_LOGIN');
		store_after_login();
		store_fb_debug('AFTER_LOGIN_SUCCESS=YES');
		return '';
	} catch (Throwable $e) {
		if ($db && $db->inTransaction()) {
			$db->rollBack();
		}
		store_fb_debug_exception($e);
		return 'Could not finish Facebook sign-in or link this account. Please try again, or use your existing sign-in method.';
	}
}
