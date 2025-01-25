<?php
class UserAuth
{
    public static function register($data)
    {
        $user = new User(null, $data['name'], $data['email'], password_hash($data['password'], PASSWORD_BCRYPT), $data['role']);
        $user->save();
        return $user;
    }

    public static function login($email, $password)
    {
        $user = User::findByEmail($email);
        if ($user && password_verify($password, $user->password)) {
            $_SESSION['user_status'] = $user->status;
            if ($user->status === "active") {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_name'] = $user->name;
                $_SESSION['user_image'] = $user->image;
                $_SESSION['user_status'] = $user->status;
                return 1;
            } 
            else if($user->status === "suspend"){
                return 2;
            }
            else {
                return 0;
            }
        } else
            return -1;
    }

    public static function logout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_image']);
        unset($_SESSION['user_status']);
        unset($_SESSION['user_name']);
        session_destroy();
    }

    public static function isAdminAuthenticated()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    public static function isBorrowerAuthenticated()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'borrower';
    }
    public static function isLenderAuthenticated()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'lender';
    }
}
