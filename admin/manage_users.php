<?php
session_start();

if (!isset($_SESSION['uid'])) {
    header('location: ../login.php');
    exit;
}

include('../dbcon.php');
require_once __DIR__ . '/role_helpers.php';
admin_sync_role_from_db($con);
if (!admin_can('users')) {
    header('location: admindash.php?denied=1');
    exit;
}

$currentId = (int) $_SESSION['uid'];
$message = '';
$messageType = '';

function admin_count(mysqli $con): int
{
    $r = mysqli_query($con, 'SELECT COUNT(*) AS c FROM `admin`');
    if (!$r) {
        return 0;
    }
    return (int) mysqli_fetch_assoc($r)['c'];
}

function username_taken(mysqli $con, string $username, ?int $exceptId = null): bool
{
    if ($exceptId === null) {
        $st = $con->prepare('SELECT `id` FROM `admin` WHERE `username` = ? LIMIT 1');
        if (!$st) {
            return true;
        }
        $st->bind_param('s', $username);
    } else {
        $st = $con->prepare('SELECT `id` FROM `admin` WHERE `username` = ? AND `id` != ? LIMIT 1');
        if (!$st) {
            return true;
        }
        $st->bind_param('si', $username, $exceptId);
    }
    $st->execute();
    $st->store_result();
    $taken = $st->num_rows > 0;
    $st->close();
    return $taken;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $uname = trim((string) ($_POST['new_username'] ?? ''));
        $p1 = (string) ($_POST['new_password'] ?? '');
        $p2 = (string) ($_POST['new_password2'] ?? '');

        if ($uname === '' || strlen($uname) > 120) {
            $message = 'Enter a valid username (1–120 characters).';
            $messageType = 'error';
        } elseif ($p1 === '' || strlen($p1) < 4) {
            $message = 'Password must be at least 4 characters.';
            $messageType = 'error';
        } elseif ($p1 !== $p2) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } elseif (username_taken($con, $uname, null)) {
            $message = 'That username is already taken.';
            $messageType = 'error';
        } else {
            $newRole = trim((string) ($_POST['new_role'] ?? 'teacher'));
            if (!rfjs_valid_admin_role($newRole)) {
                $newRole = 'teacher';
            }
            $st = $con->prepare('INSERT INTO `admin` (`username`, `password`, `role`) VALUES (?, ?, ?)');
            if ($st) {
                $st->bind_param('sss', $uname, $p1, $newRole);
                if ($st->execute()) {
                    $message = 'User created successfully.';
                    $messageType = 'ok';
                } else {
                    $message = 'Could not create user.';
                    $messageType = 'error';
                }
                $st->close();
            } else {
                $message = 'Database error. If this persists, run sql/alter_admin_role.sql in phpMyAdmin to add the role column.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['change_role'])) {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $newRole = trim((string) ($_POST['role'] ?? ''));

        if ($uid < 1) {
            $message = 'Invalid user.';
            $messageType = 'error';
        } elseif ($uid === $currentId) {
            $message = 'You cannot change your own role here. Ask another super administrator.';
            $messageType = 'error';
        } elseif (!rfjs_valid_admin_role($newRole)) {
            $message = 'Invalid role.';
            $messageType = 'error';
        } else {
            $st = $con->prepare('UPDATE `admin` SET `role` = ? WHERE `id` = ? LIMIT 1');
            if ($st) {
                $st->bind_param('si', $newRole, $uid);
                if ($st->execute()) {
                    $message = 'Role updated.';
                    $messageType = 'ok';
                } else {
                    $message = 'Could not update role. Ensure sql/alter_admin_role.sql has been applied.';
                    $messageType = 'error';
                }
                $st->close();
            } else {
                $message = 'Database error.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $p1 = (string) ($_POST['new_password'] ?? '');
        $p2 = (string) ($_POST['new_password2'] ?? '');

        if ($uid < 1) {
            $message = 'Invalid user.';
            $messageType = 'error';
        } elseif ($p1 === '' || strlen($p1) < 4) {
            $message = 'Password must be at least 4 characters.';
            $messageType = 'error';
        } elseif ($p1 !== $p2) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } else {
            $st = $con->prepare('UPDATE `admin` SET `password` = ? WHERE `id` = ? LIMIT 1');
            if ($st) {
                $st->bind_param('si', $p1, $uid);
                if ($st->execute()) {
                    $message = 'Password updated.';
                    $messageType = 'ok';
                } else {
                    $message = 'Could not update password.';
                    $messageType = 'error';
                }
                $st->close();
            } else {
                $message = 'Database error.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['rename_user'])) {
        $uid = (int) ($_POST['user_id'] ?? 0);
        $uname = trim((string) ($_POST['new_username'] ?? ''));

        if ($uid < 1) {
            $message = 'Invalid user.';
            $messageType = 'error';
        } elseif ($uname === '' || strlen($uname) > 120) {
            $message = 'Enter a valid username (1–120 characters).';
            $messageType = 'error';
        } elseif (username_taken($con, $uname, $uid)) {
            $message = 'That username is already taken.';
            $messageType = 'error';
        } else {
            $st = $con->prepare('UPDATE `admin` SET `username` = ? WHERE `id` = ? LIMIT 1');
            if ($st) {
                $st->bind_param('si', $uname, $uid);
                if ($st->execute()) {
                    $message = 'Username updated.';
                    $messageType = 'ok';
                } else {
                    $message = 'Could not update username.';
                    $messageType = 'error';
                }
                $st->close();
            } else {
                $message = 'Database error.';
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        $uid = (int) ($_POST['user_id'] ?? 0);

        if ($uid < 1) {
            $message = 'Invalid user.';
            $messageType = 'error';
        } elseif ($uid === $currentId) {
            $message = 'You cannot delete your own account while logged in. Ask another admin, or log in with a different account.';
            $messageType = 'error';
        } elseif (admin_count($con) <= 1) {
            $message = 'Cannot delete the only admin user.';
            $messageType = 'error';
        } else {
            $st = $con->prepare('DELETE FROM `admin` WHERE `id` = ? LIMIT 1');
            if ($st) {
                $st->bind_param('i', $uid);
                if ($st->execute() && $st->affected_rows === 1) {
                    $message = 'User deleted.';
                    $messageType = 'ok';
                } else {
                    $message = 'Could not delete user.';
                    $messageType = 'error';
                }
                $st->close();
            } else {
                $message = 'Database error.';
                $messageType = 'error';
            }
        }
    }
}

$users = [];
$qr = mysqli_query($con, 'SELECT `id`, `username`, `role` FROM `admin` ORDER BY `id` ASC');
if ($qr) {
    while ($row = mysqli_fetch_assoc($qr)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Manage admin users | RFJS</title>
    <link rel="icon" href="../image/logo-rfjs.png" type="image/png">
    <link rel="stylesheet" href="../csss/rfjs-theme.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Flamenco', Georgia, serif;
            background: var(--rfjs-bg-page);
            min-height: 100vh;
            min-height: 100dvh;
            padding: clamp(14px, 4vw, 24px);
            color: #333;
        }
        .wrap { max-width: min(1680px, calc(100vw - 24px)); margin: 0 auto; }
        .nav { margin-bottom: 18px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav a:hover { text-decoration: underline; }
        .card {
            background: rgba(255,255,255,.96);
            border-radius: 16px;
            padding: clamp(14px, 3vw, 24px);
            box-shadow: 0 15px 40px rgba(0,0,0,.12);
            margin-bottom: 20px;
        }
        h1 { font-size: clamp(1.15rem, 3vw, 1.45rem); color: var(--rfjs-text-accent-strong); margin-bottom: 8px; }
        .sub { color: #666; font-size: 0.95rem; margin-bottom: 16px; line-height: 1.5; }
        label { display: block; font-weight: 600; margin: 12px 0 6px; color: #444; font-size: 0.9rem; }
        input[type="text"], input[type="password"], select {
            width: 100%; max-width: 400px; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px;
            font-size: 1rem;
        }
        select.select-inline { max-width: 260px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; margin-top: 12px;
            padding: 10px 18px; border: none; border-radius: 8px; font-weight: 700;
            cursor: pointer; font-size: 0.95rem;
        }
        .btn-primary { background: var(--rfjs-gradient-btn); color: #fff; }
        .btn-danger { background: #c0392b; color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; margin-top: 0; }
        .msg { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 0.95rem; }
        .msg.ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .hint { font-size: 0.85rem; color: #666; margin-top: 8px; }
        .users-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
        table.users-table { width: max-content; min-width: 100%; border-collapse: collapse; font-size: 0.92rem; table-layout: auto; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: linear-gradient(135deg, #0d1128 0%, #5a4a42 100%); color: #faf8f5; font-weight: 600; }
        tr:hover td { background: #faf8f5; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; background: #e8e0d8; color: #5a4a42; }
        details { margin: 8px 0; }
        details summary { cursor: pointer; font-weight: 600; color: var(--rfjs-text-accent); font-size: 0.9rem; }
        details[open] summary { margin-bottom: 8px; }
        .mini-form { padding: 10px 0 6px; }
        .mini-form .row { margin-bottom: 8px; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        @media (max-width: 600px) {
            th, td { padding: 8px; font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="nav">
            <a href="admindash.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>

        <div class="card">
            <div style="text-align:center;margin-bottom:14px;">
                <img src="../image/logo-rfjs.png" alt="Royal Family Junior School" class="rfjs-logo-img rfjs-logo-crest" width="100" height="100" style="display:block;margin:0 auto;">
            </div>
            <h1><i class="fas fa-users-cog"></i> Manage admin users</h1>
            <p class="sub">
                Create logins for staff, assign <strong>roles</strong> (what each person can open on the dashboard), reset passwords, rename accounts, or remove users. Only <strong>super administrators</strong> can use this page.
            </p>

            <?php if ($message): ?>
                <div class="msg <?php echo $messageType === 'ok' ? 'ok' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <h2 style="font-size: 1.05rem; margin: 18px 0 10px; color: #444;"><i class="fas fa-user-plus"></i> Create new user</h2>
            <form method="post" action="" autocomplete="off">
                <label for="new_username">Username</label>
                <input type="text" name="new_username" id="new_username" maxlength="120" required
                    value="<?php echo isset($_POST['new_username']) ? htmlspecialchars($_POST['new_username']) : ''; ?>">

                <label for="new_password">Password</label>
                <input type="password" name="new_password" id="new_password" minlength="4" autocomplete="new-password" required>

                <label for="new_password2">Confirm password</label>
                <input type="password" name="new_password2" id="new_password2" minlength="4" autocomplete="new-password" required>

                <label for="new_role">Role</label>
                <select name="new_role" id="new_role" required>
                    <?php foreach (rfjs_admin_role_defs() as $slug => $desc): ?>
                        <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo (isset($_POST['new_role']) ? $_POST['new_role'] : 'teacher') === $slug ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($slug); ?> — <?php echo htmlspecialchars($desc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="add_user" value="1" class="btn btn-primary"><i class="fas fa-check"></i> Create user</button>
            </form>
        </div>

        <div class="card">
            <h2 style="font-size: 1.05rem; margin-bottom: 12px; color: #444;"><i class="fas fa-list"></i> All users (<?php echo count($users); ?>)</h2>
            <div class="hint">You are logged in as ID <strong><?php echo $currentId; ?></strong>. You cannot delete your own account here.</div>

            <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $id = (int) $u['id'];
                        $isSelf = $id === $currentId;
                        $ur = isset($u['role']) ? trim((string) $u['role']) : 'full';
                        if ($ur === '' || !rfjs_valid_admin_role($ur)) {
                            $ur = 'full';
                        }
                        ?>
                        <tr>
                            <td><?php echo $id; ?><?php if ($isSelf): ?> <span class="badge">you</span><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><code><?php echo htmlspecialchars($ur); ?></code></td>
                            <td>
                                <div class="actions">
                                    <?php if (!$isSelf): ?>
                                    <details>
                                        <summary>Change role</summary>
                                        <div class="mini-form">
                                            <form method="post" action="">
                                                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                                                <div class="row">
                                                    <select name="role" class="select-inline" required>
                                                        <?php foreach (rfjs_admin_role_defs() as $slug => $desc): ?>
                                                            <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $ur === $slug ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($slug); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <button type="submit" name="change_role" value="1" class="btn btn-primary btn-sm">Save role</button>
                                            </form>
                                        </div>
                                    </details>
                                    <?php endif; ?>
                                    <details>
                                        <summary>Rename</summary>
                                        <div class="mini-form">
                                            <form method="post" action="">
                                                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                                                <div class="row">
                                                    <input type="text" name="new_username" value="<?php echo htmlspecialchars($u['username']); ?>" maxlength="120" required style="max-width: 220px;">
                                                </div>
                                                <button type="submit" name="rename_user" value="1" class="btn btn-primary btn-sm">Save username</button>
                                            </form>
                                        </div>
                                    </details>
                                    <details>
                                        <summary>Reset password</summary>
                                        <div class="mini-form">
                                            <form method="post" action="" autocomplete="off">
                                                <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                                                <div class="row">
                                                    <input type="password" name="new_password" placeholder="New password" minlength="4" required autocomplete="new-password">
                                                </div>
                                                <div class="row">
                                                    <input type="password" name="new_password2" placeholder="Confirm" minlength="4" required autocomplete="new-password">
                                                </div>
                                                <button type="submit" name="reset_password" value="1" class="btn btn-primary btn-sm">Update password</button>
                                            </form>
                                        </div>
                                    </details>
                                    <?php if (!$isSelf && count($users) > 1): ?>
                                        <form method="post" action="" style="display:inline;" onsubmit="return confirm('Delete user <?php echo htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8'); ?>? This cannot be undone.');">
                                            <input type="hidden" name="user_id" value="<?php echo $id; ?>">
                                            <button type="submit" name="delete_user" value="1" class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
